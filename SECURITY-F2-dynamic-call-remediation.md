# Security finding F2: phpspa.__call unvalidated arguments

Surface: dynamic server-side function call (`phpspa.__call`)
Files:
- app/core/Impl/RealImpl/AppImpl.php (handlePhpSPARequest, the __call branch)
- app/core/Helper/FunctionCaller.php
- app/client/Component/use_function_hook.php (useFunction)
- app/core/Helper/CsrfManager.php

## What is NOT the problem (scope correction)

The function name is NOT an arbitrary-callable sink. The CSRF token binds to the
exact function name: the handler builds `new CsrfManager($functionName, CALL_FUNC_HANDLE)`
and `verifyToken` only succeeds against a session entry stored under that same name,
which exists only for a function the developer explicitly exposed with
`useFunction("myFunctionName")`. A client can expose only the one function the
developer wrapped, and can call only that function. Changing the function name in
the client-side token invalidates the token (the CSRF check fails). So there is no
cross-function replay and no reach to arbitrary PHP callables such as `system`.

## The actual defect: unvalidated arguments

The only client-controlled, unvalidated input that reaches the sink is the
arguments array:

    $res = \call_user_func_array($functionName, $data['__call']['args']);

`$data['__call']['args']` comes straight from the client (base64 JSON body) and is
passed verbatim as the arguments to the exposed function. phpspa performs no
validation, type check, count check, or sanitisation of these arguments before the
call. The exposed function receives whatever the client sends.

Impact: scoped to the behaviour of the one exposed function. If that function trusts
its arguments (file paths, SQL fragments, shell fragments, deserialisation input,
array offsets, etc.), the client controls them fully. The framework gives the
developer no server-side argument contract, so the security of the call rests
entirely on the exposed function validating its own inputs.

## What we need to do

1. Document clearly in useFunction / the __call docs that arguments arriving from
   the client are UNVALIDATED and UNTRUSTED, and that the exposed function is
   responsible for validating every argument it receives.
2. Give the framework an optional argument-validation contract on the exposed
   function (e.g. an arity check against the callable's reflection signature, and a
   per-function validator/typing hook) so a developer can opt into server-side
   argument validation instead of hand-rolling it in every exposed function.
3. At minimum, enforce an argument-count / arity check against the exposed
   function's signature before `call_user_func_array`, so a client cannot invoke it
   with an unexpected number/shape of arguments.

## Proof shape (local unit PoC, no live system)

A local unit PoC asserts only: given a validly exposed function F (token minted by
useFunction for F) and a valid CSRF token for F, the client-supplied args array is
passed to F unchanged and unvalidated. Positive control: the args reach F exactly as
sent. Negative control: a token whose function name was changed to G does NOT verify
(confirming the name is bound, so only the unvalidated-args issue remains).

Severity: High, bounded by the exposed function's own trust in its arguments (not
unauthenticated RCE from nothing).