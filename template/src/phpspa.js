(function (root, factory) {
   if (typeof define === "function" && define.amd) {
      // AMD (RequireJS)
      define([], factory);
   } else {
      // Browser globals
      var g;
      if (typeof window !== "undefined") { g = window; }
      else if (typeof self !== "undefined") { g = self; }
      else { g = this; }
      g.phpspa = factory();
   }
}(typeof self !== 'undefined' ? self : this, function () {
   'use strict';

   /**
    * UTF-8 safe base64 encoding function
    * Handles Unicode characters that btoa cannot process
    * 
    * @param {string} str - String to encode
    * @returns {string} Base64 encoded string
    */
   function utf8ToBase64(str) {
      try {
         // First try the native btoa for performance
         return btoa(str);
      } catch (e) {
         // If btoa fails (due to non-Latin1 characters), use UTF-8 safe encoding
         try {
            // Modern replacement for unescape(encodeURIComponent(str))
            const utf8Bytes = new TextEncoder().encode(str);
            const binaryString = Array.from(utf8Bytes, byte => String.fromCharCode(byte)).join('');
            return btoa(binaryString);
         } catch (fallbackError) {
            // Final fallback: encode each character individually
            return btoa(
               str.split('').map(function (c) {
                  return String.fromCharCode(c.charCodeAt(0) & 0xff);
               }).join('')
            );
         }
      }
   }

   /**
    * UTF-8 safe base64 decoding function
    * Handles Unicode characters that atob cannot process
    * 
    * @param {string} str - Base64 encoded string to decode  
    * @returns {string} Decoded string
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
      } catch (e) {
         // Fallback to regular atob
         return atob(str);
      }
   }

   // ============================================================
   // MORPHDOM LIBRARY (v2.7.0) - Efficient DOM diffing and patching
   // MIT License | https://github.com/patrick-steele-idem/morphdom
   // ============================================================
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
               if (attr.prefix === 'xmlns') {
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
      OPTION: function (fromEl, toEl) {
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
      INPUT: function (fromEl, toEl) {
         syncBooleanAttrProp(fromEl, toEl, 'checked');
         syncBooleanAttrProp(fromEl, toEl, 'disabled');

         if (fromEl.value !== toEl.value) {
            fromEl.value = toEl.value;
         }

         if (!toEl.hasAttribute('value')) {
            fromEl.removeAttribute('value');
         }
      },

      TEXTAREA: function (fromEl, toEl) {
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
      SELECT: function (fromEl, toEl) {
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
            while (curChild) {
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

   function noop() { }

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
         var addChild = options.addChild || function (parent, child) { return parent.appendChild(child); };
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
                  if (!skipFrom) { addChild(fromEl, matchingFromEl); }
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
               for (var i = 0, len = keyedRemovalList.length; i < len; i++) {
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

   // --- Initialize morphdom with the morphAttrs function ---
   var morphdom = morphdomFactory(morphAttrs);

   (function () {
      /**
       * Initialize PhpSPA when DOM is ready
       * Sets up the initial browser history state with the current page content
       */
      window.addEventListener("DOMContentLoaded", () => {
         const targetElement = document.querySelector("[data-phpspa-target]");
         const uri = location.toString();

         RuntimeManager.emit('load', {
            route: uri,
            success: true,
            error: false
         });

         if (targetElement) {
            // --- Create initial state object with current page data
            const initialState = {
               url: uri,
               title: document.title,
               targetID: targetElement.id,
               content: targetElement.innerHTML,
               root: true,
            };

            // --- Check if component has auto-reload functionality ---
            if (targetElement.hasAttribute("phpspa-reload-time")) {
               initialState.reloadTime = Number(
                  targetElement.getAttribute("phpspa-reload-time")
               );
            }

            // --- Check if component has target info ---
            if (targetElement.hasAttribute("phpspa-target-data")) {
               let targetData = targetElement.getAttribute("phpspa-target-data");
               targetData = json_decode(base64ToUtf8(targetData));

               console.log(targetData);
               // initialState.exact = true;
               // initialState.exactTargetID = targetElement.id;
               // initialState.defaultContent = base64ToUtf8()
            }

            // --- Replace current history state with PhpSPA data ---
            RuntimeManager.replaceState(
               initialState,
               document.title,
               uri
            );

            RuntimeManager.currentRoutes[initialState.targetID] = new URL(uri);

            RuntimeManager.currentState = initialState;

            // --- Set up auto-reload if specified ---
            if (targetElement.hasAttribute("phpspa-reload-time")) {
               setTimeout(phpspa.reloadComponent, initialState.reloadTime);
            }
         }
      });

      /**
       * Handle clicks on PhpSPA navigation links
       * Intercepts clicks on elements with data-type="phpspa-link-tag"
       * and routes them through the SPA navigation system
       */
      document.addEventListener("click", (event) => {
         const spaLink = event.target.closest('a[data-type="phpspa-link-tag"]');

         if (spaLink) {
            // --- Prevent default browser navigation ---
            event.preventDefault();

            // --- Navigate using PhpSPA system ---
            phpspa.navigate(new URL(spaLink.href, location.href), "push");
         }
      });

      /**
       * Handle browser back/forward button navigation
       * Restores page content when user navigates through browser history
       */
      window.addEventListener("popstate", (event) => {
         const navigationState = event.state;

         RuntimeManager.emit('beforeload', { route: location.toString() });

         // --- Enable automatic scroll restoration ---
         history.scrollRestoration = "auto";

         // --- Check if we have valid PhpSPA state data ---
         if (navigationState && navigationState.content) {
            // --- Restore page title ---
            document.title = navigationState.title ?? document.title;

            // --- Find target container or fallback to body ---
            const targetContainer =
               document.getElementById(navigationState.targetID) ?? document.body;

            const currentState = RuntimeManager.currentState;

            RuntimeManager.currentRoutes[navigationState.targetID] = navigationState.url;

            // --- Reset current state content before updating new content ---
            if (currentState?.exact === true && currentState.exactTargetID !== navigationState.targetID) {
               let currentHTML = document.getElementById(currentState.exactTargetID)
               if (currentHTML) {
                  try {
                     morphdom(currentHTML, '<div>' + currentState.defaultContent + '</div>', {
                        childrenOnly: true
                     });
                  } catch {
                     currentHTML.innerHTML = currentState.defaultContent
                  }
               }
            }

            // --- Decode and restore HTML content ---
            const updateDOM = () => {
               try {
                  morphdom(targetContainer, '<div>' + navigationState.content + '</div>', {
                     childrenOnly: true
                  });
               } catch {
                  targetContainer.innerHTML = navigationState.content;
               }
            }

            const completedDOMUpdate = () => {
               // --- Clear old executed scripts cache ---
               RuntimeManager.clearExecutedScripts();

               // --- Execute any inline scripts and styles in the restored content ---
               RuntimeManager.runAll(navigationState.root ? document.body : targetContainer);

               if (currentState.defaultContent == document.getElementById(navigationState.exactTargetID)?.innerHTML) {
                  delete RuntimeManager.currentRoutes[navigationState.exactTargetID];
               }

               // --- Save the current state ---
               RuntimeManager.currentState = navigationState

               // --- Restart auto-reload timer if needed ---
               if (typeof navigationState.reloadTime !== "undefined") {
                  setTimeout(phpspa.reloadComponent, navigationState.reloadTime);
               }

               RuntimeManager.emit('load', {
                  route: navigationState.url,
                  success: true,
                  error: false
               });
            }

            if (document.startViewTransition) {
               document.startViewTransition(updateDOM).finished.then(completedDOMUpdate).catch((reason) => {
                  RuntimeManager.emit('load', {
                     route: location.href,
                     success: false,
                     error: reason || 'Unknown error during view transition',
                  });
               });
            } else {
               updateDOM();
               completedDOMUpdate();
            }

         } else {
            // --- No valid state found - navigate to current URL to refresh ---
            phpspa.navigate(location.toString(), "replace");
         }
      });
   })();

   /**
    * A static class for managing client-side navigation and state in a PHP-powered Single Page Application (SPA).
    * Provides methods for navigation, history manipulation, event handling, and dynamic content updates.
    *
    * @class phpspa
    *
    * @method navigate
    * @static
    * @param {string|URL} url - The URL to navigate to.
    * @param {string} [state="push"] - The history state action ("push" or "replace").
    * @description Fetches content from the given URL using a custom method, updates the DOM, manages history state, and executes inline scripts.
    *
    * @method back
    * @static
    * @description Navigates back in the browser history.
    *
    * @method forward
    * @static
    * @description Navigates forward in the browser history.
    *
    * @method reload
    * @static
    * @description Reloads the current page content via SPA navigation.
    *
    * @method on
    * @static
    * @param {string} event - The event name to listen for.
    * @param {Function} callback - The callback to execute when the event is emitted.
    * @description Registers an event listener for a custom event.
    *
    */
   class phpspa {
      /**
       * Navigates to a given URL using PHPSPA's custom navigation logic.
       * Fetches the content via a custom HTTP method, updates the DOM, manages browser history,
       * emits lifecycle events, and executes inline scripts.
       *
       * @param {string|URL} url - The URL or path to navigate to.
       * @param {"push"|"replace"} [state="push"] - Determines whether to push or replace the browser history state.
       *
       * @fires phpspa#beforeload - Emitted before loading the new route.
       * @fires phpspa#load - Emitted after attempting to load the new route, with success or error status.
       */
      static navigate(url, state = "push") {
         url = url instanceof URL ? url : new URL(url, location.href);

         // --- Emit beforeload event for loading indicators ---
         RuntimeManager.emit("beforeload", { route: url });

         // --- Fetch content from the server with PhpSPA headers ---
         fetch(url, {
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
                        } catch (parseError) {
                           responseData = responseText;
                        }
                     } else {
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
                        errorData = fallbackResponse.trim().startsWith("{")
                           ? JSON.parse(fallbackResponse)
                           : fallbackResponse;
                     } catch (parseError) {
                        // --- If parsing fails, use raw text ---
                        errorData = fallbackResponse;
                     }

                     processResponse(errorData || "");

                     RuntimeManager.emit("load", {
                        route: url?.toString() || url,
                        success: false,
                        error: error.message || "Server returned an error",
                        data: errorData,
                     });
                  })
                  .catch(() => {
                     processResponse("");

                     // --- Failed to read error response body ---
                     RuntimeManager.emit("load", {
                        route: url?.toString() || url,
                        success: false,
                        error: error.message || "Failed to read error response",
                     });
                  });
            } else {
               processResponse("");

               // --- Network error, same-origin issue, or other connection problems ---
               RuntimeManager.emit("load", {
                  route: url?.toString() || url,
                  success: false,
                  error: error.message || "No connection to server",
               });
            }
         }

         /**
          * Processes the server response and updates the DOM
          * @param {string|Object} responseData - The processed response data
          */
         function processResponse(responseData) {
            // --- Update document title if provided ---
            if (
               typeof responseData?.title === "string" ||
               typeof responseData?.title === "number"
            ) {
               document.title = responseData.title;
            }

            // --- Find target element for content replacement ---
            const targetElement =
               document.getElementById(responseData?.targetID) ??
               document.getElementById(history.state?.targetID) ??
               document.body;

            // --- Reset current state content before updating new content ---
            const currentState = RuntimeManager.currentState;

            RuntimeManager.currentRoutes[responseData?.targetID || currentState.targetID] = url;

            if (currentState?.exact === true) {
               if (currentState.exactTargetID !== responseData?.targetID) {
                  let currentHTML = document.getElementById(currentState.exactTargetID)
                  if (currentHTML) {
                     try {
                        morphdom(currentHTML, '<div>' + currentState.defaultContent + '</div>', {
                           childrenOnly: true
                        });
                     } catch {
                        currentHTML.innerHTML = currentState.defaultContent;
                     }
                  }
               }
            }

            // --- Update content ---
            const updateDOM = () => {
               try {
                  morphdom(targetElement, '<div>' + responseData?.content || responseData + '</div>', {
                     childrenOnly: true
                  });
               } catch {
                  targetElement.innerHTML = responseData?.content ? responseData.content : responseData;
               }
            }

            // --- Prepare state data for browser history ---
            const stateData = {
               url: url?.toString() ?? url,
               title: responseData?.title ?? document.title,
               targetID: responseData?.targetID ?? targetElement.id,
               content: responseData?.content ?? responseData,
               defaultContent: currentState?.defaultContent,
               exact: currentState?.exact,
               exactTargetID: currentState?.exactTargetID
            };

            if (!document.getElementById(stateData.exactTargetID)) {
               stateData['exact'] = responseData?.exact || false;
               stateData['exactTargetID'] = responseData?.targetID || targetElement.id;
               stateData['defaultContent'] = document.getElementById(responseData?.targetID)?.innerHTML || '';
            }

            // --- Include reload time if specified ---
            if (typeof responseData.reloadTime !== "undefined") {
               stateData.reloadTime = responseData.reloadTime;
            }

            const completedDOMUpdate = () => {

               // --- Update browser history ---
               if (state === "push") {
                  RuntimeManager.pushState(stateData, stateData.title, url);
               } else if (state === "replace") {
                  RuntimeManager.replaceState(stateData, stateData.title, url);
               }

               if (currentState.defaultContent == document.getElementById(stateData.exactTargetID)?.innerHTML) {
                  delete RuntimeManager.currentRoutes[stateData.exactTargetID];
               }

               // --- Save the current state ---
               RuntimeManager.currentState = history.state

               // --- Handle URL fragments (hash navigation) ---
               const hashElement = document.getElementById(url?.hash?.substring(1));

               if (hashElement) {
                  scroll({
                     top: hashElement.offsetTop,
                     left: hashElement.offsetLeft,
                  });
               } else {
                  scroll(0, 0); // --- Scroll to top if no hash or element not found ---
               }

               // --- Clear old executed scripts cache ---
               RuntimeManager.clearExecutedScripts();

               // --- Execute any inline scripts and styles in the new content ---
               RuntimeManager.runAll(targetElement);


               // --- Emit successful load event ---
               RuntimeManager.emit("load", {
                  route: url?.toString() || url,
                  success: true,
                  error: false,
               });

               // --- Set up auto-reload if specified ---
               if (typeof responseData.reloadTime !== "undefined") {
                  setTimeout(phpspa.reloadComponent, responseData.reloadTime);
               }
            }

            if (document.startViewTransition) {
               document.startViewTransition(updateDOM).finished.then(completedDOMUpdate).catch((reason) => {
                  RuntimeManager.emit('load', {
                     route: url?.toString() || url,
                     success: false,
                     error: reason || 'Unknown error during view transition',
                  });
               });
            } else {
               updateDOM();
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
         phpspa.navigate(location.toString(), "replace");
      }

      /**
       * Registers a callback function to be executed when the specified event is triggered.
       *
       * @param {string} event - The name of the event to listen for.
       * @param {Function} callback - The function to call when the event is triggered.
       */
      static on(event, callback) {
         if (!RuntimeManager.events[event]) {
            RuntimeManager.events[event] = [];
         }
         RuntimeManager.events[event].push(callback);
      }

      /**
       * Updates the application state by sending a custom fetch request and updating the DOM accordingly.
       * Preserves the current scroll position during the update.
       *
       * @param {string} key - The key representing the state to update.
       * @param {string|array|object|null} value - The new value to set for the specified state key.
       * @returns {Promise<void>} A promise that resolves when the state is updated successfully.
       *
       * @example
       * phpspa.setState('user', { name: 'Alice' })
       *   .then(() => console.log('State updated!'))
       *   .catch(err => console.error('Failed to update state:', err));
       */
      static setState(key, value) {
         return new Promise(async (resolve, reject) => {
            const currentUrls = RuntimeManager.currentRoutes;
            const statePayload = JSON.stringify({ state: { key, value } });
            const promises = [];

            for (const id in currentUrls) {
               if (!Object.hasOwn(currentUrls, id)) continue;

               const url = currentUrls[id];

               const prom = fetch(url, {
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
                     } catch (parseError) {
                        responseData = responseText;
                     }
                  } else {
                     responseData = responseText || "";
                  }

                  resolve();
                  updateContent(responseData);
               } catch (error) {
                  reject(error.message);
                  handleStateError(error);
               }
            });


            /**
             * Handles errors during state update requests
             * @param {Error} error - The error that occurred
             */
            function handleStateError(error) {
               if (error.response) {
                  error.response
                     .text()
                     .then((fallbackResponse) => {
                        let errorData;

                        try {
                           errorData = fallbackResponse.trim().startsWith("{")
                              ? JSON.parse(fallbackResponse)
                              : fallbackResponse;
                        } catch (parseError) {
                           errorData = fallbackResponse;
                        }

                        updateContent(errorData || "");
                     })
                     .catch(() => {
                        updateContent("");
                     });
               } else {
                  updateContent("");
               }
            }

            /**
             * Updates the DOM content and restores scroll position
             * @param {string|Object} responseData - The response data to process
             */
            function updateContent(responseData) {
               // --- Update title if provided ---
               if (
                  typeof responseData?.title === "string" ||
                  typeof responseData?.title === "number"
               ) {
                  document.title = responseData.title;
               }

               // --- Find target element and update content ---
               const targetElement =
                  document.getElementById(responseData?.targetID) ??
                  document.getElementById(history.state?.targetID) ??
                  document.body;

               const updateDOM = () => {
                  try {
                     morphdom(targetElement, '<div>' + responseData?.content || responseData + '</div>', {
                        childrenOnly: true
                     });
                  } catch {
                     targetElement.innerHTML = responseData?.content ? responseData.content : responseData;
                  }
               };

               const completedDOMUpdate = () => {
                  // --- Clear old executed scripts cache ---
                  RuntimeManager.clearExecutedScripts();

                  // --- Execute any inline scripts and styles in the new content ---
                  RuntimeManager.runAll(targetElement);
               };

               if (document.startViewTransition) {
                  document.startViewTransition(updateDOM).finished.then(completedDOMUpdate);
               } else {
                  updateDOM();
                  completedDOMUpdate();
               }
            }
         });
      }

      /**
       * Reloads the current component content while preserving scroll position.
       * Useful for refreshing dynamic content without full page navigation.
       */
      static reloadComponent() {
         // --- Save current scroll position ---
         const currentScroll = {
            top: scrollY,
            left: scrollX,
         };

         // --- Fetch current page content ---
         fetch(new URL(location.href), {
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
                        } catch (parseError) {
                           responseData = responseText;
                        }
                     } else {
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
          * @param {Error} error - The error that occurred
          */
         function handleComponentError(error) {
            if (error.response) {
               error.response
                  .text()
                  .then((fallbackResponse) => {
                     let errorData;

                     try {
                        errorData = fallbackResponse.trim().startsWith("{")
                           ? JSON.parse(fallbackResponse)
                           : fallbackResponse;
                     } catch (parseError) {
                        errorData = fallbackResponse;
                     }

                     updateComponentContent(errorData || "");
                  })
                  .catch(() => {
                     updateComponentContent("");
                  });
            } else {
               updateComponentContent("");
            }
         }

         /**
          * Updates the component content and handles auto-reload
          * @param {string|Object} responseData - The response data
          */
         function updateComponentContent(responseData) {
            // --- Update title if provided ---
            if (
               typeof responseData?.title === "string" ||
               typeof responseData?.title === "number"
            ) {
               document.title = responseData.title;
            }

            // --- Find target and update content ---
            const targetElement =
               document.getElementById(responseData?.targetID) ??
               document.getElementById(history.state?.targetID) ??
               document.body;

            const updateDOM = () => {
               try {
                  morphdom(targetElement, '<div>' + responseData?.content || responseData + '</div>', {
                     childrenOnly: true
                  });
               } catch {
                  targetElement.innerHTML = responseData?.content || responseData;
               }
            };

            const completedDOMUpdate = () => {
               // --- Set up next auto-reload if specified ---
               if (typeof responseData.reloadTime !== "undefined") {
                  setTimeout(phpspa.reloadComponent, responseData.reloadTime);
               }
            }

            if (document.startViewTransition) {
               document.startViewTransition(updateDOM).finished.then(completedDOMUpdate);
            } else {
               updateDOM();
               completedDOMUpdate();
            }
         }
      }

      /**
       * Makes an authenticated call to the server with a token and arguments.
       * Used for server-side function calls from the client.
       *
       * @param {string} token - The authentication token for the call
       * @param {...any} args - Arguments to pass to the server function
       * @returns {Promise<string>} The decoded response from the server
       */
      static async __call(token, ...args) {
         const currentUrl = new URL(location.toString());
         const callPayload = JSON.stringify({ __call: { token, args } });

         try {
            const response = await fetch(currentUrl, {
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
               } catch (parseError) {
                  responseData = responseText;
               }
            } else {
               responseData = responseText || "";
            }

            return responseData;
         } catch (error) {
            // --- Handle errors with response bodies ---
            if (error.response) {
               try {
                  const fallbackResponse = await error.response.text();
                  let errorData;

                  try {
                     errorData = fallbackResponse.trim().startsWith("{")
                        ? JSON.parse(fallbackResponse)
                        : fallbackResponse;

                     errorData = errorData?.response
                        ? JSON.parse(errorData.response)
                        : errorData;
                  } catch (parseError) {
                     errorData = fallbackResponse;
                  }

                  return errorData;
               } catch {
                  return "";
               }
            } else {
               // --- Network errors or other issues ---
               return "";
            }
         }
      }
   }

   /**
    * Runtime Manager for PhpSPA
    *
    * Handles script execution, style injection, event management, and browser history
    * for the PhpSPA framework. Uses an obscure class name to avoid conflicts.
    *
    * @class RuntimeManager
    */
   class RuntimeManager {
      /**
       * Tracks executed scripts to prevent duplicates
       * @type {Set<string>}
       * @private
       */
      static executedScripts = new Set();

      /**
       * Tracks executed styles to prevent duplicates
       * @type {Set<string>}
       * @private
       */
      static executedStyles = new Set();

      /**
       * A static cache object that stores processed script content to avoid redundant processing.
       * Used to improve performance by caching scripts that have already been processed or compiled.
       *
       * @static
       * @type {Object<string, string>}
       * @memberof RuntimeManager
       */
      static ScriptsCachedContent = {};

      /**
       * Current history state
       *
       * @type {{
       *    url: string,
       *    title: string,
       *    targetID: string,
       *    content: string,
       *    defaultContent: string,
       *    exact: boolean,
       *    exactTargetID: string
       * }}
       */
      static currentState = {};

      /**
       * @type {Object<string, URL}>
       */
      static currentRoutes = {};

      /**
       * Internal event registry for custom events
       * @type {Object<string, Function[]>}
       * @private
       */
      static events = {
         beforeload: [],
         load: [],
      };

      /**
       * Executes inline scripts and styles within a container element
       * Processes all script and style tags, preventing duplicate execution
       *
       * @param {HTMLElement} container - The container element to search for scripts and styles
       */
      static runAll(container) {
         this.runInlineScripts(container);
         this.runInlineStyles(container);
         this.runPhpSpaScripts(container);
      }

      /**
       * Processes and executes inline scripts within a container
       * Creates isolated scopes using IIFE to prevent variable conflicts
       *
       * @param {HTMLElement} container - The container to search for script elements
       * @private
       */
      static runInlineScripts(container) {
         const scripts = container.querySelectorAll("script");
         const nonce = document.documentElement.getAttribute('x-phpspa');

         scripts.forEach((script) => {
            // --- Use base64 encoded content as unique identifier ---
            const contentHash = utf8ToBase64(script.textContent.trim());

            // --- Skip if this script has already been executed ---
            if (!this.executedScripts.has(contentHash) && script.textContent.trim() !== "") {
               this.executedScripts.add(contentHash);

               // --- Create new script element ---
               const newScript = document.createElement("script");
               newScript.nonce = nonce;

               // --- Copy all attributes except the data-type identifier ---
               for (const attribute of script.attributes) {
                  newScript.setAttribute(attribute.name, attribute.value);
               }

               // --- Check if script should run in async context ---
               const isAsync = script.hasAttribute("async");

               // --- Wrap in IIFE to create isolated scope ---
               if (isAsync) {
                  newScript.textContent = `(async function() {\n${script.textContent}\n})();`;
               } else {
                  newScript.textContent = `(function() {\n${script.textContent}\n})();`;
               }

               // --- Execute and immediately remove from DOM ---
               document.head.appendChild(newScript).remove();
            }
         });
      }


      static runPhpSpaScripts(container) {
         const scripts = container.querySelectorAll("phpspa-script, script[data-type=\"phpspa/script\"]");

         scripts.forEach(async (script) => {
            const scriptUrl = script.getAttribute('src');
            const nonce = document.documentElement.getAttribute('x-phpspa');

            // --- Skip if this script has already been executed ---
            if (!this.executedScripts.has(scriptUrl)) {
               this.executedScripts.add(scriptUrl);

               // --- Check cache first ---
               if (this.ScriptsCachedContent[scriptUrl]) {
                  const newScript = document.createElement("script");
                  newScript.textContent = this.ScriptsCachedContent[scriptUrl];
                  newScript.type = 'text/javascript';
                  newScript.nonce = nonce;

                  // --- Execute and immediately remove from DOM ---
                  document.head.appendChild(newScript).remove();
                  return;
               }

               const response = await fetch(scriptUrl, {
                  headers: {
                     "X-Requested-With": "PHPSPA_REQUEST_SCRIPT",
                  },
               });

               if (response.ok) {
                  const scriptContent = await response.text();

                  // --- Create new script element ---
                  const newScript = document.createElement("script");
                  newScript.textContent = scriptContent;
                  newScript.type = 'text/javascript';
                  newScript.nonce = nonce;

                  // --- Execute and immediately remove from DOM ---
                  document.head.appendChild(newScript).remove();

                  // --- Cache the fetched script content ---
                  this.ScriptsCachedContent[scriptUrl] = scriptContent;
               } else {
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
       * @since 1.0.0
       */
      static clearExecutedScripts() {
         RuntimeManager.executedScripts.clear();
      }

      /**
       * Processes and injects inline styles within a container
       * Prevents duplicate style injection by tracking content hashes
       *
       * @param {HTMLElement} container - The container to search for style elements
       * @private
       */
      static runInlineStyles(container) {
         const styles = container.querySelectorAll("style");
         const nonce = document.documentElement.getAttribute('x-phpspa');

         styles.forEach((style) => {
            // --- Use base64 encoded content as unique identifier ---
            const contentHash = utf8ToBase64(style.textContent.trim());

            // --- Skip if this style has already been injected ---
            if (!this.executedStyles.has(contentHash) && style.textContent.trim() !== "") {
               this.executedStyles.add(contentHash);

               // --- Create new style element ---
               const newStyle = document.createElement("style");
               newStyle.nonce = nonce;

               // --- Copy all attributes except the data-type identifier ---
               for (const attribute of style.attributes) {
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
       * @param {string} eventName - The name of the event to emit
       * @param {Object} payload - The data to pass to event listeners
       */
      static emit(eventName, payload) {
         const callbacks = this.events[eventName] || [];

         // --- Execute all registered callbacks for this event ---
         for (const callback of callbacks) {
            if (typeof callback === "function") {
               try {
                  callback(payload);
               } catch (error) {
                  // --- Log callback errors but don't break the chain ---
                  console.error(`Error in ${eventName} event callback:`, error);
               }
            }
         }
      }

      /**
       * Safely pushes a new state to browser history
       * Wraps in try-catch to handle potential browser restrictions
       *
       * @param {...any} stateArgs - Arguments to pass to history.pushState
       */
      static pushState(...stateArgs) {
         try {
            history.pushState(...stateArgs);
         } catch (error) {
            // --- Silently handle history API restrictions ---
            console.warn("Failed to push history state:", error.message);
         }
      }

      /**
       * Safely replaces current browser history state
       * Wraps in try-catch to handle potential browser restrictions
       *
       * @param {...any} stateArgs - Arguments to pass to history.replaceState
       */
      static replaceState(...stateArgs) {
         try {
            history.replaceState(...stateArgs);
         } catch (error) {
            // --- Silently handle history API restrictions ---
            console.warn("Failed to replace history state:", error.message);
         }
      }
   }

   /**
    * Global helper function for updating application state
    * Provides a convenient shorthand for phpspa.setState()
    *
    * @param {string} key - The state key to update
    * @param {any} value - The new value for the state key
    * @returns {Promise<void>} Promise that resolves when state is updated
    */
   if (typeof window !== "undefined" && typeof window.setState !== "function") {
      window.setState = (key, value) => phpspa.setState(key, value);
   }

   /**
    * Global helper function for making server calls
    * Provides a convenient shorthand for phpspa.__call()
    *
    * @param {string} token - The function token/identifier
    * @param {...any} args - Arguments to pass to the server function
    * @returns {Promise<string>} Promise that resolves with the server response
    */
   if (typeof window !== "undefined" && typeof window.__call !== "function") {
      window.__call = (token, ...args) => phpspa.__call(token, ...args);
   }

   window.RuntimeManager = RuntimeManager;

   /**
    * Export phpspa for UMD pattern
    * Returns the phpspa class to be used in different module systems
    */
   return phpspa;
}));
