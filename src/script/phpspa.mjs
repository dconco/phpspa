/*!
 * PhpSPA Client Runtime v2.0.10
 * Docs: https://phpspa.tech | Package: @dconco/phpspa
 * License: MIT
 */
/**
 * UTF-8 safe base64 encoding function
 * Handles Unicode characters that btoa cannot process
 */
function utf8ToBase64(str) {
    try {
        // First try the native btoa for performance
        return btoa(str);
    }
    catch (e) {
        // If btoa fails (due to non-Latin1 characters), use UTF-8 safe encoding
        try {
            // Modern replacement for unescape(encodeURIComponent(str))
            const utf8Bytes = new TextEncoder().encode(str);
            const binaryString = Array.from(utf8Bytes, byte => String.fromCharCode(byte)).join('');
            return btoa(binaryString);
        }
        catch (fallbackError) {
            // Final fallback: encode each character individually
            return btoa(str.split('').map(function (c) {
                return String.fromCharCode(c.charCodeAt(0) & 0xff);
            }).join(''));
        }
    }
}
/**
 * UTF-8 safe base64 decoding function
 * Handles Unicode characters that atob cannot process
 */
function base64ToUtf8(str) {
    try {
        // Try modern UTF-8 safe decoding first
        const binaryString = atob(str);
        const bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return new TextDecoder().decode(bytes);
    }
    catch (e) {
        // Fallback to regular atob
        return atob(str);
    }
}

/**
 * Runtime Manager for PhpSPA
 *
 * Handles script execution, style injection, event management, and browser history
 * for the PhpSPA framework. Uses an obscure class name to avoid conflicts.
 */
class RuntimeManager {
    /**
     * Tracks executed scripts to prevent duplicates
     */
    static executedScripts = new Set();
    /**
     * Tracks executed styles to prevent duplicates
     */
    static executedStyles = new Set();
    /**
     * A static cache object that stores processed script content to avoid redundant processing.
     * Used to improve performance by caching scripts that have already been processed or compiled.
     */
    static ScriptsCachedContent = {};
    /**
     * This contains all routes for the current page
     */
    static currentRoutes = {};
    static events = {
        beforeload: [],
        load: [],
    };
    static currentStateData;
    /**
     * Caches the last payload for each emitted event so late listeners can replay it
     */
    static lastEventPayload = {};
    static effects = new Set();
    static memoizedCallbacks = [];
    /**
     * Registers a side effect to be executed when state changes
     * similar to React's useEffect but using state keys strings as dependencies
     *
     * @param {Function} callback - The effect callback
     * @param {Array<string>} dependencies - Array of state keys to listen for
     */
    static registerEffect(callback, dependencies = null) {
        // --- Run immediately (mount) ---
        const cleanup = callback();
        const effect = {
            callback,
            dependencies,
            cleanup: typeof cleanup === 'function' ? cleanup : null,
            lastDeps: dependencies ? RuntimeManager.resolveDependencies(dependencies) : null
        };
        RuntimeManager.effects.add(effect);
    }
    /**
     * Triggers effects that depend on the specific state key
     *
     * @param key - The state key that changed
     * @param value - The new value (optional)
     */
    static triggerEffects(key, value) {
        RuntimeManager.effects.forEach(effect => {
            if (!effect.dependencies || effect.dependencies.length === 0) {
                RuntimeManager.invokeEffect(effect, effect.dependencies);
                return;
            }
            const nextDeps = RuntimeManager.resolveDependencies(effect.dependencies);
            if (!effect.lastDeps || !RuntimeManager.depsEqual(effect.lastDeps, nextDeps)) {
                RuntimeManager.invokeEffect(effect, nextDeps);
            }
        });
    }
    /**
     * Clears all registered effects and runs their cleanup functions
     */
    static clearEffects() {
        RuntimeManager.effects.forEach(effect => {
            if (effect.cleanup)
                effect.cleanup();
        });
        RuntimeManager.effects.clear();
    }
    static depsEqual(a, b) {
        if (a === b)
            return true;
        if (!a || !b)
            return false;
        if (a.length !== b.length)
            return false;
        return a.every((dep, index) => Object.is(dep, b[index]));
    }
    static registerCallback(callback, dependencies = []) {
        const resolvedDeps = RuntimeManager.resolveDependencies(dependencies);
        const existing = RuntimeManager.memoizedCallbacks.find(entry => entry.deps.length === dependencies.length && RuntimeManager.depsEqual(entry.resolvedDeps, resolvedDeps));
        if (existing) {
            return existing.callback;
        }
        const memoized = callback.bind(undefined);
        RuntimeManager.memoizedCallbacks.push({ deps: dependencies.slice(), resolvedDeps, callback: memoized });
        return memoized;
    }
    static resolveDependencies(dependencies) {
        return dependencies.map(dep => RuntimeManager.resolveDependency(dep));
    }
    static resolveDependency(dependency) {
        if (typeof dependency === 'string' &&
            RuntimeManager.currentStateData &&
            Object.prototype.hasOwnProperty.call(RuntimeManager.currentStateData, dependency)) {
            return RuntimeManager.currentStateData[dependency];
        }
        return dependency;
    }
    static invokeEffect(effect, nextDeps) {
        if (effect.cleanup)
            effect.cleanup();
        const cleanup = effect.callback();
        effect.cleanup = typeof cleanup === 'function' ? cleanup : null;
        effect.lastDeps = nextDeps ? nextDeps.slice() : nextDeps;
    }
    static runScripts() {
        for (const targetID in RuntimeManager.currentRoutes) {
            const element = document.getElementById(targetID);
            if (element) {
                this.runInlineScripts(element);
                this.runPhpSpaScripts(element);
            }
        }
    }
    static runStyles() {
        for (const targetID in RuntimeManager.currentRoutes) {
            const element = document.getElementById(targetID);
            if (element) {
                this.runInlineStyles(element);
            }
        }
    }
    /**
     * Processes and executes inline scripts within a container
     * Creates isolated scopes using IIFE to prevent variable conflicts
     */
    static runInlineScripts(container) {
        const scripts = container.querySelectorAll("script");
        const nonce = document.head.getAttribute('x-phpspa');
        scripts.forEach((script) => {
            // --- Use base64 encoded content as unique identifier ---
            const contentHash = utf8ToBase64(script.textContent.trim());
            // --- Skip if this script has already been executed ---
            if (!this.executedScripts.has(contentHash) && script.textContent.trim() !== "") {
                this.executedScripts.add(contentHash);
                // --- Create new script element ---
                const newScript = document.createElement("script");
                newScript.nonce = nonce ?? undefined;
                // --- Copy all attributes except the data-type identifier ---
                for (const attribute of Array.from(script.attributes)) {
                    newScript.setAttribute(attribute.name, attribute.value);
                }
                newScript.textContent = `(()=>{\n${script.textContent}\n})()`;
                // --- Execute and immediately remove from DOM ---
                document.head.appendChild(newScript).remove();
            }
        });
    }
    static runPhpSpaScripts(container) {
        const scripts = container.querySelectorAll("phpspa-script, script[data-type=\"phpspa/script\"]");
        const nonce = document.head.getAttribute('x-phpspa');
        scripts.forEach(async (script) => {
            const scriptUrl = script.getAttribute('src') ?? '';
            const scriptType = script.getAttribute('type') ?? '';
            // --- Skip if this script has already been executed ---
            if (!this.executedScripts.has(scriptUrl)) {
                this.executedScripts.add(scriptUrl);
                // --- Check cache first ---
                if (this.ScriptsCachedContent[scriptUrl]) {
                    const newScript = document.createElement("script");
                    newScript.textContent = `(()=>{\n${this.ScriptsCachedContent[scriptUrl]}\n})()`;
                    newScript.nonce = nonce ?? undefined;
                    newScript.type = scriptType;
                    // --- Execute and immediately remove from DOM ---
                    document.head.appendChild(newScript).remove();
                    return;
                }
                const response = await fetch(scriptUrl, {
                    headers: {
                        "X-Requested-With": "PHPSPA_REQUEST_SCRIPT",
                    }
                });
                if (response.ok) {
                    const scriptContent = await response.text();
                    // --- Create new script element ---
                    const newScript = document.createElement("script");
                    newScript.textContent = `(()=>{\n${scriptContent}\n})()`;
                    newScript.nonce = nonce ?? undefined;
                    newScript.type = scriptType;
                    // --- Execute and immediately remove from DOM ---
                    document.head.appendChild(newScript).remove();
                    // --- Cache the fetched script content ---
                    this.ScriptsCachedContent[scriptUrl] = scriptContent;
                }
                else {
                    console.error(`Failed to load script from ${scriptUrl}: ${response.statusText}`);
                }
            }
        });
    }
    /**
     * Clears all executed scripts from the runtime manager.
     * This method removes all entries from the executedScripts collection,
     * effectively resetting the tracking of previously executed scripts.
     *
     * @static
     * @memberof RuntimeManager
     */
    static clearExecutedScripts() {
        RuntimeManager.executedScripts.clear();
    }
    /**
     * Processes and injects inline styles within a container
     * Prevents duplicate style injection by tracking content hashes
     */
    static runInlineStyles(container) {
        const styles = container.querySelectorAll("style");
        const nonce = document.head.getAttribute('x-phpspa');
        styles.forEach((style) => {
            // --- Use base64 encoded content as unique identifier ---
            const contentHash = utf8ToBase64(style.textContent.trim());
            // --- Skip if this style has already been injected ---
            if (!this.executedStyles.has(contentHash) && style.textContent.trim() !== "") {
                this.executedStyles.add(contentHash);
                // --- Create new style element ---
                const newStyle = document.createElement("style");
                newStyle.nonce = nonce ?? undefined;
                // --- Copy all attributes except the data-type identifier ---
                for (const attribute of Array.from(style.attributes)) {
                    newStyle.setAttribute(attribute.name, attribute.value);
                }
                // --- Copy style content and inject into head ---
                newStyle.textContent = style.textContent;
                document.head.appendChild(newStyle).remove();
            }
        });
    }
    /**
     * Emits a custom event to all registered listeners
     * Used for lifecycle events like 'beforeload' and 'load'
     *
     * @param eventName - The name of the event to emit
     * @param payload - The data to pass to event listeners
     */
    static emit(eventName, payload) {
        const callbacks = this.events[eventName] || [];
        this.lastEventPayload[eventName] = payload;
        // --- Execute all registered callbacks for this event ---
        for (const callback of callbacks) {
            if (typeof callback === "function") {
                try {
                    callback(payload);
                }
                catch (error) {
                    // --- Log callback errors but don't break the chain ---
                    console.error(`Error in ${eventName} event callback:`, error);
                }
            }
        }
    }
    /**
     * Returns the last cached payload for an event, if available
     */
    static getLastEventPayload(eventName) {
        return this.lastEventPayload[eventName];
    }
    /**
     * Safely pushes a new state to browser history
     * Wraps in try-catch to handle potential browser restrictions
     */
    static pushState(data, unused, url) {
        try {
            history.pushState(data, unused, url);
        }
        catch (error) {
            // --- Silently handle history API restrictions ---
            console.warn("Failed to push history state:", error instanceof Error ? error.message : error);
        }
    }
    /**
     * Safely replaces current browser history state
     * Wraps in try-catch to handle potential browser restrictions
     */
    static replaceState(data, unused, url) {
        try {
            history.replaceState(data, unused, url);
        }
        catch (error) {
            // --- Silently handle history API restrictions ---
            console.warn("Failed to replace history state:", error instanceof Error ? error.message : error);
        }
    }
}

var DOCUMENT_FRAGMENT_NODE = 11;

function morphAttrs(fromNode, toNode) {
    var toNodeAttrs = toNode.attributes;
    var attr;
    var attrName;
    var attrNamespaceURI;
    var attrValue;
    var fromValue;

    // document-fragments dont have attributes so lets not do anything
    if (toNode.nodeType === DOCUMENT_FRAGMENT_NODE || fromNode.nodeType === DOCUMENT_FRAGMENT_NODE) {
      return;
    }

    // update attributes on original DOM element
    for (var i = toNodeAttrs.length - 1; i >= 0; i--) {
        attr = toNodeAttrs[i];
        attrName = attr.name;
        attrNamespaceURI = attr.namespaceURI;
        attrValue = attr.value;

        if (attrNamespaceURI) {
            attrName = attr.localName || attrName;
            fromValue = fromNode.getAttributeNS(attrNamespaceURI, attrName);

            if (fromValue !== attrValue) {
                if (attr.prefix === 'xmlns'){
                    attrName = attr.name; // It's not allowed to set an attribute with the XMLNS namespace without specifying the `xmlns` prefix
                }
                fromNode.setAttributeNS(attrNamespaceURI, attrName, attrValue);
            }
        } else {
            fromValue = fromNode.getAttribute(attrName);

            if (fromValue !== attrValue) {
                fromNode.setAttribute(attrName, attrValue);
            }
        }
    }

    // Remove any extra attributes found on the original DOM element that
    // weren't found on the target element.
    var fromNodeAttrs = fromNode.attributes;

    for (var d = fromNodeAttrs.length - 1; d >= 0; d--) {
        attr = fromNodeAttrs[d];
        attrName = attr.name;
        attrNamespaceURI = attr.namespaceURI;

        if (attrNamespaceURI) {
            attrName = attr.localName || attrName;

            if (!toNode.hasAttributeNS(attrNamespaceURI, attrName)) {
                fromNode.removeAttributeNS(attrNamespaceURI, attrName);
            }
        } else {
            if (!toNode.hasAttribute(attrName)) {
                fromNode.removeAttribute(attrName);
            }
        }
    }
}

var range; // Create a range object for efficently rendering strings to elements.
var NS_XHTML = 'http://www.w3.org/1999/xhtml';

var doc = typeof document === 'undefined' ? undefined : document;
var HAS_TEMPLATE_SUPPORT = !!doc && 'content' in doc.createElement('template');
var HAS_RANGE_SUPPORT = !!doc && doc.createRange && 'createContextualFragment' in doc.createRange();

function createFragmentFromTemplate(str) {
    var template = doc.createElement('template');
    template.innerHTML = str;
    return template.content.childNodes[0];
}

function createFragmentFromRange(str) {
    if (!range) {
        range = doc.createRange();
        range.selectNode(doc.body);
    }

    var fragment = range.createContextualFragment(str);
    return fragment.childNodes[0];
}

function createFragmentFromWrap(str) {
    var fragment = doc.createElement('body');
    fragment.innerHTML = str;
    return fragment.childNodes[0];
}

/**
 * This is about the same
 * var html = new DOMParser().parseFromString(str, 'text/html');
 * return html.body.firstChild;
 *
 * @method toElement
 * @param {String} str
 */
function toElement(str) {
    str = str.trim();
    if (HAS_TEMPLATE_SUPPORT) {
      // avoid restrictions on content for things like `<tr><th>Hi</th></tr>` which
      // createContextualFragment doesn't support
      // <template> support not available in IE
      return createFragmentFromTemplate(str);
    } else if (HAS_RANGE_SUPPORT) {
      return createFragmentFromRange(str);
    }

    return createFragmentFromWrap(str);
}

/**
 * Returns true if two node's names are the same.
 *
 * NOTE: We don't bother checking `namespaceURI` because you will never find two HTML elements with the same
 *       nodeName and different namespace URIs.
 *
 * @param {Element} a
 * @param {Element} b The target element
 * @return {boolean}
 */
function compareNodeNames(fromEl, toEl) {
    var fromNodeName = fromEl.nodeName;
    var toNodeName = toEl.nodeName;
    var fromCodeStart, toCodeStart;

    if (fromNodeName === toNodeName) {
        return true;
    }

    fromCodeStart = fromNodeName.charCodeAt(0);
    toCodeStart = toNodeName.charCodeAt(0);

    // If the target element is a virtual DOM node or SVG node then we may
    // need to normalize the tag name before comparing. Normal HTML elements that are
    // in the "http://www.w3.org/1999/xhtml"
    // are converted to upper case
    if (fromCodeStart <= 90 && toCodeStart >= 97) { // from is upper and to is lower
        return fromNodeName === toNodeName.toUpperCase();
    } else if (toCodeStart <= 90 && fromCodeStart >= 97) { // to is upper and from is lower
        return toNodeName === fromNodeName.toUpperCase();
    } else {
        return false;
    }
}

/**
 * Create an element, optionally with a known namespace URI.
 *
 * @param {string} name the element name, e.g. 'div' or 'svg'
 * @param {string} [namespaceURI] the element's namespace URI, i.e. the value of
 * its `xmlns` attribute or its inferred namespace.
 *
 * @return {Element}
 */
function createElementNS(name, namespaceURI) {
    return !namespaceURI || namespaceURI === NS_XHTML ?
        doc.createElement(name) :
        doc.createElementNS(namespaceURI, name);
}

/**
 * Copies the children of one DOM element to another DOM element
 */
function moveChildren(fromEl, toEl) {
    var curChild = fromEl.firstChild;
    while (curChild) {
        var nextChild = curChild.nextSibling;
        toEl.appendChild(curChild);
        curChild = nextChild;
    }
    return toEl;
}

function syncBooleanAttrProp(fromEl, toEl, name) {
    if (fromEl[name] !== toEl[name]) {
        fromEl[name] = toEl[name];
        if (fromEl[name]) {
            fromEl.setAttribute(name, '');
        } else {
            fromEl.removeAttribute(name);
        }
    }
}

var specialElHandlers = {
    OPTION: function(fromEl, toEl) {
        var parentNode = fromEl.parentNode;
        if (parentNode) {
            var parentName = parentNode.nodeName.toUpperCase();
            if (parentName === 'OPTGROUP') {
                parentNode = parentNode.parentNode;
                parentName = parentNode && parentNode.nodeName.toUpperCase();
            }
            if (parentName === 'SELECT' && !parentNode.hasAttribute('multiple')) {
                if (fromEl.hasAttribute('selected') && !toEl.selected) {
                    // Workaround for MS Edge bug where the 'selected' attribute can only be
                    // removed if set to a non-empty value:
                    // https://developer.microsoft.com/en-us/microsoft-edge/platform/issues/12087679/
                    fromEl.setAttribute('selected', 'selected');
                    fromEl.removeAttribute('selected');
                }
                // We have to reset select element's selectedIndex to -1, otherwise setting
                // fromEl.selected using the syncBooleanAttrProp below has no effect.
                // The correct selectedIndex will be set in the SELECT special handler below.
                parentNode.selectedIndex = -1;
            }
        }
        syncBooleanAttrProp(fromEl, toEl, 'selected');
    },
    /**
     * The "value" attribute is special for the <input> element since it sets
     * the initial value. Changing the "value" attribute without changing the
     * "value" property will have no effect since it is only used to the set the
     * initial value.  Similar for the "checked" attribute, and "disabled".
     */
    INPUT: function(fromEl, toEl) {
        syncBooleanAttrProp(fromEl, toEl, 'checked');
        syncBooleanAttrProp(fromEl, toEl, 'disabled');

        if (fromEl.value !== toEl.value) {
            fromEl.value = toEl.value;
        }

        if (!toEl.hasAttribute('value')) {
            fromEl.removeAttribute('value');
        }
    },

    TEXTAREA: function(fromEl, toEl) {
        var newValue = toEl.value;
        if (fromEl.value !== newValue) {
            fromEl.value = newValue;
        }

        var firstChild = fromEl.firstChild;
        if (firstChild) {
            // Needed for IE. Apparently IE sets the placeholder as the
            // node value and vise versa. This ignores an empty update.
            var oldValue = firstChild.nodeValue;

            if (oldValue == newValue || (!newValue && oldValue == fromEl.placeholder)) {
                return;
            }

            firstChild.nodeValue = newValue;
        }
    },
    SELECT: function(fromEl, toEl) {
        if (!toEl.hasAttribute('multiple')) {
            var selectedIndex = -1;
            var i = 0;
            // We have to loop through children of fromEl, not toEl since nodes can be moved
            // from toEl to fromEl directly when morphing.
            // At the time this special handler is invoked, all children have already been morphed
            // and appended to / removed from fromEl, so using fromEl here is safe and correct.
            var curChild = fromEl.firstChild;
            var optgroup;
            var nodeName;
            while(curChild) {
                nodeName = curChild.nodeName && curChild.nodeName.toUpperCase();
                if (nodeName === 'OPTGROUP') {
                    optgroup = curChild;
                    curChild = optgroup.firstChild;
                    // handle empty optgroups
                    if (!curChild) {
                        curChild = optgroup.nextSibling;
                        optgroup = null;
                    }
                } else {
                    if (nodeName === 'OPTION') {
                        if (curChild.hasAttribute('selected')) {
                            selectedIndex = i;
                            break;
                        }
                        i++;
                    }
                    curChild = curChild.nextSibling;
                    if (!curChild && optgroup) {
                        curChild = optgroup.nextSibling;
                        optgroup = null;
                    }
                }
            }

            fromEl.selectedIndex = selectedIndex;
        }
    }
};

var ELEMENT_NODE = 1;
var DOCUMENT_FRAGMENT_NODE$1 = 11;
var TEXT_NODE = 3;
var COMMENT_NODE = 8;

function noop() {}

function defaultGetNodeKey(node) {
  if (node) {
    return (node.getAttribute && node.getAttribute('id')) || node.id;
  }
}

function morphdomFactory(morphAttrs) {

  return function morphdom(fromNode, toNode, options) {
    if (!options) {
      options = {};
    }

    if (typeof toNode === 'string') {
      if (fromNode.nodeName === '#document' || fromNode.nodeName === 'HTML' || fromNode.nodeName === 'BODY') {
        var toNodeHtml = toNode;
        toNode = doc.createElement('html');
        toNode.innerHTML = toNodeHtml;
      } else {
        toNode = toElement(toNode);
      }
    } else if (toNode.nodeType === DOCUMENT_FRAGMENT_NODE$1) {
      toNode = toNode.firstElementChild;
    }

    var getNodeKey = options.getNodeKey || defaultGetNodeKey;
    var onBeforeNodeAdded = options.onBeforeNodeAdded || noop;
    var onNodeAdded = options.onNodeAdded || noop;
    var onBeforeElUpdated = options.onBeforeElUpdated || noop;
    var onElUpdated = options.onElUpdated || noop;
    var onBeforeNodeDiscarded = options.onBeforeNodeDiscarded || noop;
    var onNodeDiscarded = options.onNodeDiscarded || noop;
    var onBeforeElChildrenUpdated = options.onBeforeElChildrenUpdated || noop;
    var skipFromChildren = options.skipFromChildren || noop;
    var addChild = options.addChild || function(parent, child){ return parent.appendChild(child); };
    var childrenOnly = options.childrenOnly === true;

    // This object is used as a lookup to quickly find all keyed elements in the original DOM tree.
    var fromNodesLookup = Object.create(null);
    var keyedRemovalList = [];

    function addKeyedRemoval(key) {
      keyedRemovalList.push(key);
    }

    function walkDiscardedChildNodes(node, skipKeyedNodes) {
      if (node.nodeType === ELEMENT_NODE) {
        var curChild = node.firstChild;
        while (curChild) {

          var key = undefined;

          if (skipKeyedNodes && (key = getNodeKey(curChild))) {
            // If we are skipping keyed nodes then we add the key
            // to a list so that it can be handled at the very end.
            addKeyedRemoval(key);
          } else {
            // Only report the node as discarded if it is not keyed. We do this because
            // at the end we loop through all keyed elements that were unmatched
            // and then discard them in one final pass.
            onNodeDiscarded(curChild);
            if (curChild.firstChild) {
              walkDiscardedChildNodes(curChild, skipKeyedNodes);
            }
          }

          curChild = curChild.nextSibling;
        }
      }
    }

    /**
    * Removes a DOM node out of the original DOM
    *
    * @param  {Node} node The node to remove
    * @param  {Node} parentNode The nodes parent
    * @param  {Boolean} skipKeyedNodes If true then elements with keys will be skipped and not discarded.
    * @return {undefined}
    */
    function removeNode(node, parentNode, skipKeyedNodes) {
      if (onBeforeNodeDiscarded(node) === false) {
        return;
      }

      if (parentNode) {
        parentNode.removeChild(node);
      }

      onNodeDiscarded(node);
      walkDiscardedChildNodes(node, skipKeyedNodes);
    }

    // // TreeWalker implementation is no faster, but keeping this around in case this changes in the future
    // function indexTree(root) {
    //     var treeWalker = document.createTreeWalker(
    //         root,
    //         NodeFilter.SHOW_ELEMENT);
    //
    //     var el;
    //     while((el = treeWalker.nextNode())) {
    //         var key = getNodeKey(el);
    //         if (key) {
    //             fromNodesLookup[key] = el;
    //         }
    //     }
    // }

    // // NodeIterator implementation is no faster, but keeping this around in case this changes in the future
    //
    // function indexTree(node) {
    //     var nodeIterator = document.createNodeIterator(node, NodeFilter.SHOW_ELEMENT);
    //     var el;
    //     while((el = nodeIterator.nextNode())) {
    //         var key = getNodeKey(el);
    //         if (key) {
    //             fromNodesLookup[key] = el;
    //         }
    //     }
    // }

    function indexTree(node) {
      if (node.nodeType === ELEMENT_NODE || node.nodeType === DOCUMENT_FRAGMENT_NODE$1) {
        var curChild = node.firstChild;
        while (curChild) {
          var key = getNodeKey(curChild);
          if (key) {
            fromNodesLookup[key] = curChild;
          }

          // Walk recursively
          indexTree(curChild);

          curChild = curChild.nextSibling;
        }
      }
    }

    indexTree(fromNode);

    function handleNodeAdded(el) {
      onNodeAdded(el);

      var curChild = el.firstChild;
      while (curChild) {
        var nextSibling = curChild.nextSibling;

        var key = getNodeKey(curChild);
        if (key) {
          var unmatchedFromEl = fromNodesLookup[key];
          // if we find a duplicate #id node in cache, replace `el` with cache value
          // and morph it to the child node.
          if (unmatchedFromEl && compareNodeNames(curChild, unmatchedFromEl)) {
            curChild.parentNode.replaceChild(unmatchedFromEl, curChild);
            morphEl(unmatchedFromEl, curChild);
          } else {
            handleNodeAdded(curChild);
          }
        } else {
          // recursively call for curChild and it's children to see if we find something in
          // fromNodesLookup
          handleNodeAdded(curChild);
        }

        curChild = nextSibling;
      }
    }

    function cleanupFromEl(fromEl, curFromNodeChild, curFromNodeKey) {
      // We have processed all of the "to nodes". If curFromNodeChild is
      // non-null then we still have some from nodes left over that need
      // to be removed
      while (curFromNodeChild) {
        var fromNextSibling = curFromNodeChild.nextSibling;
        if ((curFromNodeKey = getNodeKey(curFromNodeChild))) {
          // Since the node is keyed it might be matched up later so we defer
          // the actual removal to later
          addKeyedRemoval(curFromNodeKey);
        } else {
          // NOTE: we skip nested keyed nodes from being removed since there is
          //       still a chance they will be matched up later
          removeNode(curFromNodeChild, fromEl, true /* skip keyed nodes */);
        }
        curFromNodeChild = fromNextSibling;
      }
    }

    function morphEl(fromEl, toEl, childrenOnly) {
      var toElKey = getNodeKey(toEl);

      if (toElKey) {
        // If an element with an ID is being morphed then it will be in the final
        // DOM so clear it out of the saved elements collection
        delete fromNodesLookup[toElKey];
      }

      if (!childrenOnly) {
        // optional
        var beforeUpdateResult = onBeforeElUpdated(fromEl, toEl);
        if (beforeUpdateResult === false) {
          return;
        } else if (beforeUpdateResult instanceof HTMLElement) {
          fromEl = beforeUpdateResult;
          // reindex the new fromEl in case it's not in the same
          // tree as the original fromEl
          // (Phoenix LiveView sometimes returns a cloned tree,
          //  but keyed lookups would still point to the original tree)
          indexTree(fromEl);
        }

        // update attributes on original DOM element first
        morphAttrs(fromEl, toEl);
        // optional
        onElUpdated(fromEl);

        if (onBeforeElChildrenUpdated(fromEl, toEl) === false) {
          return;
        }
      }

      if (fromEl.nodeName !== 'TEXTAREA') {
        morphChildren(fromEl, toEl);
      } else {
        specialElHandlers.TEXTAREA(fromEl, toEl);
      }
    }

    function morphChildren(fromEl, toEl) {
      var skipFrom = skipFromChildren(fromEl, toEl);
      var curToNodeChild = toEl.firstChild;
      var curFromNodeChild = fromEl.firstChild;
      var curToNodeKey;
      var curFromNodeKey;

      var fromNextSibling;
      var toNextSibling;
      var matchingFromEl;

      // walk the children
      outer: while (curToNodeChild) {
        toNextSibling = curToNodeChild.nextSibling;
        curToNodeKey = getNodeKey(curToNodeChild);

        // walk the fromNode children all the way through
        while (!skipFrom && curFromNodeChild) {
          fromNextSibling = curFromNodeChild.nextSibling;

          if (curToNodeChild.isSameNode && curToNodeChild.isSameNode(curFromNodeChild)) {
            curToNodeChild = toNextSibling;
            curFromNodeChild = fromNextSibling;
            continue outer;
          }

          curFromNodeKey = getNodeKey(curFromNodeChild);

          var curFromNodeType = curFromNodeChild.nodeType;

          // this means if the curFromNodeChild doesnt have a match with the curToNodeChild
          var isCompatible = undefined;

          if (curFromNodeType === curToNodeChild.nodeType) {
            if (curFromNodeType === ELEMENT_NODE) {
              // Both nodes being compared are Element nodes

              if (curToNodeKey) {
                // The target node has a key so we want to match it up with the correct element
                // in the original DOM tree
                if (curToNodeKey !== curFromNodeKey) {
                  // The current element in the original DOM tree does not have a matching key so
                  // let's check our lookup to see if there is a matching element in the original
                  // DOM tree
                  if ((matchingFromEl = fromNodesLookup[curToNodeKey])) {
                    if (fromNextSibling === matchingFromEl) {
                      // Special case for single element removals. To avoid removing the original
                      // DOM node out of the tree (since that can break CSS transitions, etc.),
                      // we will instead discard the current node and wait until the next
                      // iteration to properly match up the keyed target element with its matching
                      // element in the original tree
                      isCompatible = false;
                    } else {
                      // We found a matching keyed element somewhere in the original DOM tree.
                      // Let's move the original DOM node into the current position and morph
                      // it.

                      // NOTE: We use insertBefore instead of replaceChild because we want to go through
                      // the `removeNode()` function for the node that is being discarded so that
                      // all lifecycle hooks are correctly invoked
                      fromEl.insertBefore(matchingFromEl, curFromNodeChild);

                      // fromNextSibling = curFromNodeChild.nextSibling;

                      if (curFromNodeKey) {
                        // Since the node is keyed it might be matched up later so we defer
                        // the actual removal to later
                        addKeyedRemoval(curFromNodeKey);
                      } else {
                        // NOTE: we skip nested keyed nodes from being removed since there is
                        //       still a chance they will be matched up later
                        removeNode(curFromNodeChild, fromEl, true /* skip keyed nodes */);
                      }

                      curFromNodeChild = matchingFromEl;
                      curFromNodeKey = getNodeKey(curFromNodeChild);
                    }
                  } else {
                    // The nodes are not compatible since the "to" node has a key and there
                    // is no matching keyed node in the source tree
                    isCompatible = false;
                  }
                }
              } else if (curFromNodeKey) {
                // The original has a key
                isCompatible = false;
              }

              isCompatible = isCompatible !== false && compareNodeNames(curFromNodeChild, curToNodeChild);
              if (isCompatible) {
                // We found compatible DOM elements so transform
                // the current "from" node to match the current
                // target DOM node.
                // MORPH
                morphEl(curFromNodeChild, curToNodeChild);
              }

            } else if (curFromNodeType === TEXT_NODE || curFromNodeType == COMMENT_NODE) {
              // Both nodes being compared are Text or Comment nodes
              isCompatible = true;
              // Simply update nodeValue on the original node to
              // change the text value
              if (curFromNodeChild.nodeValue !== curToNodeChild.nodeValue) {
                curFromNodeChild.nodeValue = curToNodeChild.nodeValue;
              }

            }
          }

          if (isCompatible) {
            // Advance both the "to" child and the "from" child since we found a match
            // Nothing else to do as we already recursively called morphChildren above
            curToNodeChild = toNextSibling;
            curFromNodeChild = fromNextSibling;
            continue outer;
          }

          // No compatible match so remove the old node from the DOM and continue trying to find a
          // match in the original DOM. However, we only do this if the from node is not keyed
          // since it is possible that a keyed node might match up with a node somewhere else in the
          // target tree and we don't want to discard it just yet since it still might find a
          // home in the final DOM tree. After everything is done we will remove any keyed nodes
          // that didn't find a home
          if (curFromNodeKey) {
            // Since the node is keyed it might be matched up later so we defer
            // the actual removal to later
            addKeyedRemoval(curFromNodeKey);
          } else {
            // NOTE: we skip nested keyed nodes from being removed since there is
            //       still a chance they will be matched up later
            removeNode(curFromNodeChild, fromEl, true /* skip keyed nodes */);
          }

          curFromNodeChild = fromNextSibling;
        } // END: while(curFromNodeChild) {}

        // If we got this far then we did not find a candidate match for
        // our "to node" and we exhausted all of the children "from"
        // nodes. Therefore, we will just append the current "to" node
        // to the end
        if (curToNodeKey && (matchingFromEl = fromNodesLookup[curToNodeKey]) && compareNodeNames(matchingFromEl, curToNodeChild)) {
          // MORPH
          if(!skipFrom){ addChild(fromEl, matchingFromEl); }
          morphEl(matchingFromEl, curToNodeChild);
        } else {
          var onBeforeNodeAddedResult = onBeforeNodeAdded(curToNodeChild);
          if (onBeforeNodeAddedResult !== false) {
            if (onBeforeNodeAddedResult) {
              curToNodeChild = onBeforeNodeAddedResult;
            }

            if (curToNodeChild.actualize) {
              curToNodeChild = curToNodeChild.actualize(fromEl.ownerDocument || doc);
            }
            addChild(fromEl, curToNodeChild);
            handleNodeAdded(curToNodeChild);
          }
        }

        curToNodeChild = toNextSibling;
        curFromNodeChild = fromNextSibling;
      }

      cleanupFromEl(fromEl, curFromNodeChild, curFromNodeKey);

      var specialElHandler = specialElHandlers[fromEl.nodeName];
      if (specialElHandler) {
        specialElHandler(fromEl, toEl);
      }
    } // END: morphChildren(...)

    var morphedNode = fromNode;
    var morphedNodeType = morphedNode.nodeType;
    var toNodeType = toNode.nodeType;

    if (!childrenOnly) {
      // Handle the case where we are given two DOM nodes that are not
      // compatible (e.g. <div> --> <span> or <div> --> TEXT)
      if (morphedNodeType === ELEMENT_NODE) {
        if (toNodeType === ELEMENT_NODE) {
          if (!compareNodeNames(fromNode, toNode)) {
            onNodeDiscarded(fromNode);
            morphedNode = moveChildren(fromNode, createElementNS(toNode.nodeName, toNode.namespaceURI));
          }
        } else {
          // Going from an element node to a text node
          morphedNode = toNode;
        }
      } else if (morphedNodeType === TEXT_NODE || morphedNodeType === COMMENT_NODE) { // Text or comment node
        if (toNodeType === morphedNodeType) {
          if (morphedNode.nodeValue !== toNode.nodeValue) {
            morphedNode.nodeValue = toNode.nodeValue;
          }

          return morphedNode;
        } else {
          // Text node to something else
          morphedNode = toNode;
        }
      }
    }

    if (morphedNode === toNode) {
      // The "to node" was not compatible with the "from node" so we had to
      // toss out the "from node" and use the "to node"
      onNodeDiscarded(fromNode);
    } else {
      if (toNode.isSameNode && toNode.isSameNode(morphedNode)) {
        return;
      }

      morphEl(morphedNode, toNode, childrenOnly);

      // We now need to loop over any keyed nodes that might need to be
      // removed. We only do the removal if we know that the keyed node
      // never found a match. When a keyed node is matched up we remove
      // it out of fromNodesLookup and we use fromNodesLookup to determine
      // if a keyed node has been matched up or not
      if (keyedRemovalList) {
        for (var i=0, len=keyedRemovalList.length; i<len; i++) {
          var elToRemove = fromNodesLookup[keyedRemovalList[i]];
          if (elToRemove) {
            removeNode(elToRemove, elToRemove.parentNode, false);
          }
        }
      }
    }

    if (!childrenOnly && morphedNode !== fromNode && fromNode.parentNode) {
      if (morphedNode.actualize) {
        morphedNode = morphedNode.actualize(fromNode.ownerDocument || doc);
      }
      // If we had to swap out the from node with a new node because the old
      // node was not compatible with the target node then we need to
      // replace the old DOM node in the original DOM tree. This is only
      // possible if the original DOM node was part of a DOM tree which
      // we know is the case if it has a parent node.
      fromNode.parentNode.replaceChild(morphedNode, fromNode);
    }

    return morphedNode;
  };
}

var morphdom = morphdomFactory(morphAttrs);

class AppManager {
    static currentStateData = {};
    /**
     * Navigates to a given URL using PHPSPA's custom navigation logic.
     * Fetches the content via a custom HTTP method, updates the DOM, manages browser history,
     * emits lifecycle events, and executes inline scripts.
     *
     * @param url - The URL or path to navigate to.
     * @param state Determines whether to push or replace the browser history state.
     *
     * @fires AppManager#beforeload - Emitted before loading the new route.
     * @fires AppManager#load - Emitted after attempting to load the new route, with success or error status.
     */
    static navigate(url, state = "push") {
        const newUrl = url instanceof URL ? url : new URL(url, location.toString());
        // --- Emit beforeload event for loading indicators ---
        RuntimeManager.emit("beforeload", { route: newUrl.toString() });
        // --- Fetch content from the server with PhpSPA headers ---
        fetch(newUrl, {
            headers: {
                "X-Requested-With": "PHPSPA_REQUEST",
                "X-Phpspa-Target": "navigate",
            },
            mode: "same-origin",
            redirect: "follow",
            keepalive: true,
        })
            .then((response) => {
            response
                .text()
                .then((responseText) => {
                let responseData;
                // --- Try to parse JSON response, fallback to raw text ---
                if (responseText && responseText.trim().startsWith("{")) {
                    try {
                        responseData = JSON.parse(responseText);
                    }
                    catch (parseError) {
                        responseData = responseText;
                    }
                }
                else {
                    responseData = responseText || ""; // --- Handle empty responses ---
                }
                processResponse(responseData);
            })
                .catch((error) => handleError(error));
        })
            .catch((error) => handleError(error));
        /**
         * Handles errors that occur during navigation requests
         * @param {Error} error - The error object from the failed request
         */
        function handleError(error) {
            // --- Check if the error has a response body (HTTP 4xx/5xx errors) ---
            if (error.response) {
                error.response
                    .text()
                    .then((fallbackResponse) => {
                    let errorData;
                    try {
                        // --- Attempt to parse error response as JSON ---
                        errorData = fallbackResponse?.trim().startsWith("{")
                            ? JSON.parse(fallbackResponse)
                            : fallbackResponse;
                    }
                    catch (parseError) {
                        // --- If parsing fails, use raw text ---
                        errorData = fallbackResponse;
                    }
                    processResponse(errorData || "");
                    RuntimeManager.emit("load", {
                        route: newUrl.toString(),
                        success: false,
                        error: error.message || "Server returned an error",
                        data: errorData,
                    });
                })
                    .catch(() => {
                    processResponse("");
                    // --- Failed to read error response body ---
                    RuntimeManager.emit("load", {
                        route: newUrl.toString(),
                        success: false,
                        error: error.message || "Failed to read error response",
                    });
                });
            }
            else {
                processResponse("");
                // --- Network error, same-origin issue, or other connection problems ---
                RuntimeManager.emit("load", {
                    route: newUrl.toString(),
                    success: false,
                    error: error.message || "No connection to server",
                });
            }
        }
        /**
         * Processes the server response and updates the DOM
         */
        function processResponse(responseData) {
            const component = typeof responseData === 'string'
                ? { content: responseData, stateData: {} }
                : responseData;
            RuntimeManager.currentStateData = component.stateData;
            // --- Update document title if provided ---
            if (component?.title && component.title.length > 0) {
                document.title = component.title;
            }
            // --- Find target element for content replacement ---
            const targetElement = document.getElementById(component?.targetID) ??
                document.getElementById(history.state?.targetID) ??
                document.body;
            if (component?.targetID) {
                RuntimeManager.currentRoutes[component.targetID] = {
                    route: newUrl,
                    exact: component.exact ?? false,
                    defaultContent: RuntimeManager.currentRoutes[component.targetID]?.defaultContent ?? targetElement.innerHTML
                };
            }
            const currentRoutes = RuntimeManager.currentRoutes;
            for (const targetID in currentRoutes) {
                if (!Object.hasOwn(currentRoutes, targetID))
                    continue;
                const targetInfo = currentRoutes[targetID];
                // --- If route is exact and the route target ID is not equal to the navigated route target ID ---
                // --- Then the document URL has changed ---
                // --- That is they are navigating away ---
                // --- And any route with exact === true must go back to its default content ---
                if (targetInfo.exact === true && targetID !== component?.targetID) {
                    let currentHTML = document.getElementById(targetID);
                    if (currentHTML) {
                        try {
                            morphdom(currentHTML, '<div>' + targetInfo.defaultContent + '</div>', {
                                childrenOnly: true
                            });
                        }
                        catch {
                            currentHTML.innerHTML = targetInfo.defaultContent;
                        }
                    }
                    delete currentRoutes[targetID];
                }
            }
            // --- Update content ---
            const updateDOM = () => {
                targetElement.style.visibility = 'hidden'; // --- Hide during update ---
                try {
                    morphdom(targetElement, '<div>' + component.content + '</div>', {
                        childrenOnly: true
                    });
                }
                catch {
                    targetElement.innerHTML = component.content;
                }
                // --- Execute any inline styles in the new content ---
                RuntimeManager.runStyles();
            };
            const stateData = {
                url: newUrl.toString(),
                title: component?.title ?? document.title,
                targetID: targetElement.id,
                content: component.content,
                exact: currentRoutes[component?.targetID]?.exact,
                defaultContent: currentRoutes[component?.targetID]?.defaultContent,
            };
            // --- Include reload time if specified ---
            if (component?.reloadTime) {
                stateData.reloadTime = component.reloadTime;
            }
            const completedDOMUpdate = () => {
                // --- Update browser history ---
                if (state === "push") {
                    RuntimeManager.pushState(stateData, stateData.title, newUrl);
                }
                else if (state === "replace") {
                    RuntimeManager.replaceState(stateData, stateData.title, newUrl);
                }
                // --- Handle URL fragments (hash navigation) ---
                const hashElement = document.getElementById(newUrl.hash.substring(1));
                if (hashElement) {
                    scroll({
                        top: hashElement.offsetTop,
                        left: hashElement.offsetLeft,
                    });
                }
                else {
                    scroll(0, 0); // --- Scroll to top if no hash or element not found ---
                }
                // --- Clear old executed scripts cache ---
                RuntimeManager.clearEffects();
                RuntimeManager.clearExecutedScripts();
                // --- Execute any inline scripts in the new content ---
                RuntimeManager.runScripts();
                // --- Show the updated content after all scripts and styles are processed ---
                requestAnimationFrame(() => {
                    targetElement.style.visibility = 'visible';
                });
                // --- Emit successful load event ---
                RuntimeManager.emit("load", {
                    route: newUrl.toString(),
                    success: true,
                    error: false,
                });
                // --- Set up auto-reload if specified ---
                if (component?.reloadTime) {
                    setTimeout(AppManager.reloadComponent, component.reloadTime);
                }
            };
            if (document.startViewTransition) {
                document.startViewTransition(updateDOM).finished.then(completedDOMUpdate).catch((reason) => {
                    RuntimeManager.emit('load', {
                        route: newUrl.toString(),
                        success: false,
                        error: reason || 'Unknown error during view transition',
                    });
                    // --- Show content even if view transition failed ---
                    requestAnimationFrame(() => {
                        targetElement.style.visibility = 'visible';
                    });
                });
            }
            else {
                updateDOM();
                completedDOMUpdate();
            }
        }
    }
    /**
     * Navigates back in the browser history.
     * Uses the native browser history API.
     */
    static back() {
        history.back();
    }
    /**
     * Navigates forward in the browser's session history.
     * Uses the native browser history API.
     */
    static forward() {
        history.forward();
    }
    /**
     * Reloads the current page by navigating to the current URL using the "replace" history mode.
     * This does not add a new entry to the browser's history stack.
     */
    static reload() {
        AppManager.navigate(location.toString(), "replace");
    }
    /**
     * Registers a callback function to be executed when the specified event is triggered.
     *
     * @param event - The name of the event to listen for.
     * @param callback - The function to call when the event is triggered.
     */
    static on(event, callback) {
        if (!RuntimeManager.events[event]) {
            RuntimeManager.events[event] = [];
        }
        RuntimeManager.events[event].push(callback);
        const lastPayload = RuntimeManager.getLastEventPayload(event);
        if (lastPayload) {
            try {
                callback(lastPayload);
            }
            catch (error) {
                console.error(`Error in ${event} event callback:`, error);
            }
        }
    }
    /**
     * Registers a side effect to be executed after component updates.
     * Alias for RuntimeManager.registerEffect.
     *
     * @param callback - The effect callback
     * @param dependencies - Array of state keys to listen for
     */
    static useEffect(callback, dependencies = null) {
        RuntimeManager.registerEffect(callback, dependencies);
    }
    static useCallback(callback, dependencies = []) {
        return RuntimeManager.registerCallback(callback, dependencies);
    }
    /**
     * Updates the application state by sending a custom fetch request and updating the DOM accordingly.
     * Preserves the current scroll position during the update.
     *
     * @param key - The key representing the state to update.
     * @param value - The new value to set for the specified state key.
     * @returns A promise that resolves when the state is updated successfully.
     *
     * @example
     * setState('user', { name: 'Alice' })
     *   .then(() => console.log('State updated!'))
     *   .catch(err => console.error('Failed to update state:', err))
     */
    static setState(key, value) {
        if (typeof value === 'function') {
            value = value(RuntimeManager.currentStateData[key]);
        }
        return new Promise(async (resolve, reject) => {
            const currentRoutes = RuntimeManager.currentRoutes;
            const statePayload = JSON.stringify({ state: { key, value } });
            const promises = [];
            for (const targetID in currentRoutes) {
                if (!Object.hasOwn(currentRoutes, targetID))
                    continue;
                const { route } = currentRoutes[targetID];
                const prom = fetch(route, {
                    headers: {
                        "X-Requested-With": "PHPSPA_REQUEST",
                        Authorization: `Bearer ${utf8ToBase64(statePayload)}`,
                    },
                    mode: "same-origin",
                    redirect: "follow",
                    keepalive: true,
                });
                promises.push(prom);
            }
            const responses = await Promise.all(promises);
            responses.forEach(async (response) => {
                try {
                    const responseText = await response.text();
                    let responseData;
                    // --- Parse response as JSON if possible ---
                    if (responseText && responseText.trim().startsWith("{")) {
                        try {
                            responseData = JSON.parse(responseText);
                        }
                        catch (parseError) {
                            responseData = responseText;
                        }
                    }
                    else {
                        responseData = responseText || "";
                    }
                    resolve();
                    updateContent(responseData);
                }
                catch (error) {
                    reject(error);
                    handleStateError(error);
                }
            });
            /**
             * Handles errors during state update requests
             */
            function handleStateError(error) {
                if (error?.response) {
                    error.response
                        .text()
                        .then((fallbackResponse) => {
                        let errorData;
                        try {
                            errorData = fallbackResponse?.trim().startsWith("{")
                                ? JSON.parse(fallbackResponse)
                                : fallbackResponse;
                        }
                        catch (parseError) {
                            errorData = fallbackResponse;
                        }
                        updateContent(errorData || "");
                    })
                        .catch(() => {
                        updateContent("");
                    });
                }
                else {
                    updateContent("");
                }
            }
            /**
             * Updates the DOM content and restores scroll position
             * @param {string|Object} responseData - The response data to process
             */
            function updateContent(responseData) {
                const component = typeof responseData === 'string'
                    ? { content: responseData, stateData: {} }
                    : responseData;
                RuntimeManager.currentStateData = component.stateData;
                // --- Update title if provided ---
                if (component?.title && String(component.title).length > 0) {
                    document.title = component.title;
                }
                // --- Find target element and update content ---
                const targetElement = document.getElementById(component?.targetID) ??
                    document.getElementById(history.state?.targetID) ??
                    document.body;
                const updateDOM = () => {
                    try {
                        morphdom(targetElement, '<div>' + component.content + '</div>', {
                            childrenOnly: true
                        });
                    }
                    catch {
                        targetElement.innerHTML = component.content;
                    }
                };
                const completedDOMUpdate = () => {
                    // --- Trigger effects for the changed key ---
                    RuntimeManager.triggerEffects(key, value);
                };
                updateDOM();
                completedDOMUpdate();
            }
        });
    }
    /**
     * Reloads the current component content while preserving scroll position.
     * Useful for refreshing dynamic content without full page navigation.
     */
    static reloadComponent() {
        // --- Fetch current page content ---
        fetch(location.toString(), {
            headers: {
                "X-Requested-With": "PHPSPA_REQUEST",
            },
            mode: "same-origin",
            redirect: "follow",
            keepalive: true,
        })
            .then((response) => {
            response
                .text()
                .then((responseText) => {
                let responseData;
                // --- Parse response ---
                if (responseText && responseText.trim().startsWith("{")) {
                    try {
                        responseData = JSON.parse(responseText);
                    }
                    catch (parseError) {
                        responseData = responseText;
                    }
                }
                else {
                    responseData = responseText || "";
                }
                updateComponentContent(responseData);
            })
                .catch((error) => {
                handleComponentError(error);
            });
        })
            .catch((error) => {
            handleComponentError(error);
        });
        /**
         * Handles errors during component reload
         */
        function handleComponentError(error) {
            if (error?.response) {
                error.response
                    .text()
                    .then((fallbackResponse) => {
                    let errorData;
                    try {
                        errorData = fallbackResponse?.trim().startsWith("{")
                            ? JSON.parse(fallbackResponse)
                            : fallbackResponse;
                    }
                    catch (parseError) {
                        errorData = fallbackResponse;
                    }
                    updateComponentContent(errorData || "");
                })
                    .catch(() => {
                    updateComponentContent("");
                });
            }
            else {
                updateComponentContent("");
            }
        }
        /**
         * Updates the component content and handles auto-reload
         */
        function updateComponentContent(responseData) {
            const component = typeof responseData === 'string'
                ? { content: responseData, stateData: {} }
                : responseData;
            RuntimeManager.currentStateData = component.stateData;
            // --- Update title if provided ---
            if (component?.title && String(component.title).length > 0) {
                document.title = component.title;
            }
            // --- Find target and update content ---
            const targetElement = document.getElementById(component?.targetID) ??
                document.getElementById(history.state?.targetID) ??
                document.body;
            const updateDOM = () => {
                targetElement.style.visibility = 'hidden'; // --- Hide during update ---
                try {
                    morphdom(targetElement, '<div>' + component.content + '</div>', {
                        childrenOnly: true
                    });
                }
                catch {
                    targetElement.innerHTML = component.content;
                }
                // --- Execute any inline styles in the new content ---
                RuntimeManager.runStyles();
            };
            const completedDOMUpdate = () => {
                // --- Clear old executed scripts cache ---
                RuntimeManager.clearEffects();
                RuntimeManager.clearExecutedScripts();
                // --- Execute any inline scripts in the new content ---
                RuntimeManager.runScripts();
                // --- Show the updated content after all scripts and styles are processed ---
                requestAnimationFrame(() => {
                    targetElement.style.visibility = 'visible';
                });
                // --- Set up next auto-reload if specified ---
                if (component?.reloadTime) {
                    setTimeout(AppManager.reloadComponent, component.reloadTime);
                }
            };
            updateDOM();
            completedDOMUpdate();
        }
    }
    /**
     * Makes an authenticated call to the server with a token and arguments.
     * Used for server-side function calls from the client.
     *
     * @param token - The authentication token for the call
     * @param args - Arguments to pass to the server function
     * @returns The decoded response from the server
     */
    static async __call(token, ...args) {
        const callPayload = JSON.stringify({ __call: { token, args } });
        try {
            const response = await fetch(location.pathname, {
                headers: {
                    "X-Requested-With": "PHPSPA_REQUEST",
                    Authorization: `Bearer ${utf8ToBase64(callPayload)}`,
                },
                mode: "same-origin",
                redirect: "follow",
                keepalive: true,
            });
            const responseText = await response.text();
            let responseData;
            // --- Parse and decode response ---
            if (responseText && responseText.trim().startsWith("{")) {
                try {
                    responseData = JSON.parse(responseText);
                    responseData = responseData?.response
                        ? JSON.parse(responseData.response)
                        : responseData;
                }
                catch (parseError) {
                    responseData = responseText;
                }
            }
            else {
                responseData = responseText || "";
            }
            return responseData;
        }
        catch (error) {
            // --- Handle errors with response bodies ---
            if (error?.response) {
                try {
                    const fallbackResponse = await error.response.text();
                    let errorData;
                    try {
                        errorData = fallbackResponse?.trim().startsWith("{")
                            ? JSON.parse(fallbackResponse)
                            : fallbackResponse;
                        errorData = errorData?.response
                            ? JSON.parse(errorData.response)
                            : errorData;
                    }
                    catch (parseError) {
                        errorData = fallbackResponse;
                    }
                    return errorData;
                }
                catch {
                    return "";
                }
            }
            else {
                // --- Network errors or other issues ---
                return "";
            }
        }
    }
}

/**
 * Intercepts clicks on Component.Link generated anchors and routes through AppManager
 */
function setupLinkInterception() {
    document.addEventListener("click", (event) => {
        if (event.defaultPrevented || event.button !== 0)
            return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)
            return;
        const target = event.target?.closest?.('a[data-type="phpspa-link-tag"]');
        if (!target)
            return;
        if (target.hasAttribute('download'))
            return;
        if (target.getAttribute('target') && target.getAttribute('target') !== '_self')
            return;
        const href = target.getAttribute('href');
        if (!href)
            return;
        const url = new URL(href, location.toString());
        if (url.origin !== location.origin)
            return; // --- external links fallback to default behaviour ---
        event.preventDefault();
        AppManager.navigate(url, 'push');
    });
}

/**
 * Bootstraps PhpSPA runtime by caching current route info
 * and wiring up history/navigation handlers
 */
function bootstrap() {
    const targetElement = document.querySelector("[data-phpspa-target]");
    const targetElementInfo = document.querySelector("[phpspa-target-data]");
    const uri = location.toString();
    RuntimeManager.emit('load', {
        route: uri,
        success: true,
        error: false
    });
    if (targetElement) {
        /**
         *  Create initial state object with current page data
         */
        const initialState = {
            url: uri,
            title: document.title,
            targetID: targetElement.id,
            content: targetElement.innerHTML,
            root: true,
        };
        // --- Check if component has target info ---
        if (targetElementInfo) {
            const targetData = targetElementInfo.getAttribute("phpspa-target-data");
            const targetDataInfo = JSON.parse(base64ToUtf8(targetData ?? ''));
            // --- Check if component has auto-reload functionality ---
            if (targetDataInfo.reloadTime)
                initialState.reloadTime = targetDataInfo.reloadTime;
            RuntimeManager.currentStateData = targetDataInfo.stateData;
            targetDataInfo.targetIDs.forEach((targetID, index) => {
                const exact = targetDataInfo.exact[index];
                const defaultContent = targetDataInfo.defaultContent[index];
                if (targetID === targetElement.id) {
                    initialState.exact = exact;
                    initialState.defaultContent = defaultContent;
                }
                RuntimeManager.currentRoutes[targetID] = {
                    route: new URL(targetDataInfo.currentRoutes[index], uri),
                    defaultContent,
                    exact
                };
            });
        }
        // --- Replace current history state with PhpSPA data ---
        RuntimeManager.replaceState(initialState, document.title, uri);
        // --- Set up auto-reload if specified ---
        if (initialState.reloadTime) {
            setTimeout(AppManager.reloadComponent, initialState.reloadTime);
        }
    }
    setupLinkInterception();
}

const navigateHistory = (event) => {
    const navigationState = event.state;
    RuntimeManager.emit('beforeload', { route: location.toString() });
    // --- Enable automatic scroll restoration ---
    history.scrollRestoration = "auto";
    // --- Check if we have valid PhpSPA state data ---
    if (navigationState && navigationState.content) {
        // --- Restore page title ---
        document.title = navigationState.title ?? document.title;
        // --- Find target container or fallback to body ---
        const targetContainer = document.getElementById(navigationState.targetID);
        if (!targetContainer) {
            location.reload();
            return;
        }
        if (navigationState.targetID) {
            RuntimeManager.currentRoutes[navigationState.targetID] = {
                route: new URL(navigationState.url),
                exact: navigationState.exact ?? false,
                defaultContent: navigationState.defaultContent || ''
            };
        }
        const currentRoutes = RuntimeManager.currentRoutes;
        for (const targetID in currentRoutes) {
            if (!Object.hasOwn(currentRoutes, targetID))
                continue;
            const targetInfo = currentRoutes[targetID];
            // --- If route is exact and the route target ID is not equal to the navigated route target ID ---
            // --- Then the document URL has changed ---
            // --- That is they are navigating away ---
            // --- And any route with exact === true must go back to its default content ---
            if (targetInfo.exact === true && targetID !== navigationState.targetID) {
                let currentHTML = document.getElementById(targetID);
                if (currentHTML) {
                    try {
                        morphdom(currentHTML, '<div>' + targetInfo.defaultContent + '</div>', {
                            childrenOnly: true
                        });
                    }
                    catch {
                        currentHTML.innerHTML = targetInfo.defaultContent;
                    }
                }
                delete currentRoutes[targetID];
            }
        }
        // --- Decode and restore HTML content ---
        const updateDOM = () => {
            targetContainer.style.visibility = 'hidden'; // --- Hide during update ---
            try {
                morphdom(targetContainer, '<div>' + navigationState.content + '</div>', {
                    childrenOnly: true
                });
            }
            catch {
                targetContainer.innerHTML = navigationState.content;
            }
            // --- Execute any inline styles in the new content ---
            RuntimeManager.runStyles();
        };
        const completedDOMUpdate = () => {
            // --- Clear old executed scripts cache ---
            RuntimeManager.clearEffects();
            RuntimeManager.clearExecutedScripts();
            // --- Execute any inline scripts in the restored content ---
            RuntimeManager.runScripts();
            // --- Show the updated content after all scripts and styles are processed ---
            requestAnimationFrame(() => {
                targetContainer.style.visibility = 'visible';
            });
            // --- Restart auto-reload timer if needed ---
            if (navigationState?.reloadTime) {
                setTimeout(AppManager.reloadComponent, navigationState.reloadTime);
            }
            RuntimeManager.emit('load', {
                route: navigationState.url,
                success: true,
                error: false
            });
        };
        if (document.startViewTransition) {
            document.startViewTransition(updateDOM).finished.then(completedDOMUpdate).catch((reason) => {
                RuntimeManager.emit('load', {
                    route: location.href,
                    success: false,
                    error: reason || 'Unknown error during view transition',
                });
            });
            // --- Show content even if view transition failed ---
            requestAnimationFrame(() => {
                targetContainer.style.visibility = 'visible';
            });
        }
        else {
            updateDOM();
            completedDOMUpdate();
        }
    }
    else {
        // --- No valid state found - reload current URL to refresh ---
        location.reload();
    }
};

class phpspa extends AppManager {
}
// --- Ensure bootstrap runs even if script loads after DOMContentLoaded ---
const readyStates = ["interactive", "complete"];
if (document.readyState === "loading") {
    window.addEventListener("DOMContentLoaded", bootstrap, { once: true });
}
else if (readyStates.includes(document.readyState)) {
    bootstrap();
}
// --- Handle browser back/forward button navigation ---
// --- Restores page content when user navigates through browser history ---
window.addEventListener("popstate", navigateHistory);
if (typeof window !== "undefined") {
    window.phpspa = phpspa;
    if (window.setState !== phpspa.setState)
        window.setState = phpspa.setState;
    if (window.__call !== phpspa.__call)
        window.__call = phpspa.__call;
    if (window.useEffect !== phpspa.useEffect)
        window.useEffect = phpspa.useEffect;
    if (window.useCallback !== phpspa.useCallback)
        window.useCallback = phpspa.useCallback;
}
const setState = phpspa.setState.bind(phpspa);
const useEffect = phpspa.useEffect.bind(phpspa);
const useCallback = phpspa.useCallback.bind(phpspa);
const __call = phpspa.__call.bind(phpspa);

export { __call, phpspa as default, setState, useCallback, useEffect };
