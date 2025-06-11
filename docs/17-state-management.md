# 🧠 State Management

phpSPA supports **reactive state** between the frontend and backend — no full page reload required.

You define state in PHP using `createState()`, and update it from JavaScript using `phpspa.setState()`.

Let’s break it down with a basic **counter app**.

---

## 1️⃣ Define State in PHP

Inside your component function, you define state like this:

```php
use function phpSPA\Component\createState;

$counter = createState('count', 0); // 'count' is the unique state key
```

* `'count'` is the **state key** — it identifies what part of state you're working with.
* `0` is the default value used on first load.
* `$counter()` gets the current value (after any updates from frontend).

---

## 2️⃣ Use the Value in Your Component

```php
return <<<HTML
   <div>
      <h1>Counter: $counter</h1>
      <button id="increment">Increment</button>
   </div>

   <script data-type="phpspa/script">
      document.getElementById('increment').addEventListener('click', () => {
         phpspa.setState('count', $counter + 1);
      });
   </script>
HTML;
```

* When the user clicks the button, the frontend tells the backend: **"Set `counter` to new value."**
* phpSPA runs the component again (on the server) with the updated state.
* The new HTML is swapped in automatically — **without a full page reload**.

---

## 🔄 What Just Happened?

Here’s the full cycle of `createState()` and `phpspa.setState()`:

| Step                                 | What Happens                             |
| ------------------------------------ | ---------------------------------------- |
| 1. PHP: `createState('count', 0)`    | Registers the state with a default value |
| 2. JS: `phpspa.setState('count', 5)` | Sends new state value to server          |
| 3. phpSPA re-renders                 | Component gets re-run with `count = 5`   |
| 4. Result                            | HTML updates without reloading the page  |

---

## ⚠️ Notes

* The **first argument** is always the **state key**.
* Each state key is **shared globally** across all components — if you use the same key in different components, it will be shared.
* `setState()` always returns a promise — you can `.then()` it to do something after render.

---

## 🛠 Updating State from PHP

You’re not limited to frontend updates — you can also update state **inside your PHP component** by calling the state function with a new value:

```php
$counter = createState('count', 0);

if (someCondition()) {
    $counter(4); // This updates the 'count' state on the server
}

$count = $counter(); // Now $count is 4
```

### 🔄 Why Use This?

* Set state based on form submissions or backend logic
* Reset or initialize values after an action
* Fully control flow without needing JavaScript

> ☝️ After setting the value, you can still access the **updated value** by calling the function again.

---

## ✅ Full Working Counter Component

```php
use phpSPA\Component;
use function phpSPA\Component\createState;

function Counter(): string
{
   $counter = createState('count', 0);

   return <<<HTML
      <div>
         <h2>Count: $counter</h2>
         <button id="inc">Count</button>
      </div>

      <script data-type="phpspa/script">
         document.getElementById('inc').addEventListener('click', () => {
            phpspa.setState('count', $counter + 1);
         });
      </script>
   HTML;
}

return (new Component('Counter'))
   ->route('/counter')
   ->method('GET')
   ->title('Counter Example');
```

---

## ⚠️ Manual Setup: Including `createState` Without Composer

If you’re **not using Composer**, or for some reason the autoloading isn't working correctly, you’ll need to manually include the `createState` function.

### ✅ Step 1: Include the `createState` File

Make sure you add this at the top of your component file:

```php
include_once __DIR__ . '/vendor/dconco/phpspa/app/core/Component/createState.php';
```

Adjust the path if you're not using `vendor/` or if you're keeping `phpSPA` somewhere else.

### ✅ Step 2: Import the Function Namespace

Right after your `include_once`, don’t forget to **import the namespace** for the state function:

```php
use function phpSPA\Component\createState;
```

> ☝️ `createState` is defined in a namespaced file — you must use `use function` or it won’t work.

---
