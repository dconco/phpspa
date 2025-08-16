/**
 * phpSPA JavaScript Engine
 *
 * A lightweight JavaScript engine for PHP-powered single-page applications.
 * Handles SPA-style navigation, content replacement, and lifecycle events
 * without full page reloads. Designed to pair with the `phpSPA` PHP framework.
 *
 * Features:
 * - `phpspa.navigate(url, state = "push")`: Navigate to a new route via AJAX.
 * - `phpspa.back()` / `phpspa.forward()`: Navigate browser history.
 * - `phpspa.on("beforeload" | "load", callback)`: Lifecycle event hooks.
 * - Auto-replaces DOM target with component content and updates `<title>` and `<meta>`.
 * - Executes inline component scripts marked as `<script data-type="phpspa/script">`.
 * - Built-in scroll position restoration across route changes.
 *
 * Example Usage:
 * ```js
 * phpspa.on("beforeload", ({ route }) => showSpinner());
 * phpspa.on("load", ({ success }) => hideSpinner());
 * phpspa.navigate("/profile");
 * ```
 *
 * Note:
 * - All scripts and logic must be attached per component using `$component->script(...)`.
 * - This library assumes server-rendered HTML responses with placeholder target IDs.
 *
 * @author Dave Conco
 * @version 1.1.7
 * @license MIT
 */
;(function () {
	window.addEventListener('DOMContentLoaded', () => {
		const __target = document.querySelector('[data-phpspa-target]')

		if (__target) {
			const __state = {
				__url__: location.href,
				__title__: document.title,
				__targetID__: __target.parentElement.id,
				__content__: btoa(__target.innerHTML),
			}

			if (__target.hasAttribute('phpspa-reload-time')) {
				__state['__reloadTime__'] = Number(
					__target.getAttribute('phpspa-reload-time')
				)
			}

			__PHPSPA_RUNTIME_MANAGER__.__replaceState__(
				__state,
				document.title,
				location.href
			)

			if (__target.hasAttribute('phpspa-reload-time')) {
				setTimeout(phpspa.reloadComponent, __state.__reloadTime__)
			}
		}
	})

	document.addEventListener('click', __ => {
		const __info = __.target.closest('a[data-type="phpspa-link-tag"]')

		if (__info) {
			__.preventDefault()
			phpspa.navigate(new URL(__info.href, location.href), 'push')
		}
	})

	window.addEventListener('popstate', __ => {
		const __state = __.state

		if (
			__state &&
			__state.__url__ &&
			__state.__targetID__ &&
			__state.__content__
		) {
			document.title = __state.__title__ ?? document.title

			let __targetElement =
				document.getElementById(__state.__targetID__) ?? document.body

			__targetElement.innerHTML = atob(__state.__content__)
			__PHPSPA_RUNTIME_MANAGER__.__run_all__(__targetElement)

			if (typeof __state['__reloadTime__'] !== 'undefined') {
				setTimeout(phpspa.reloadComponent, __state.__reloadTime__)
			}
		} else {
			phpspa.navigate(new URL(location.href), 'replace')
		}

		history.scrollRestoration = 'auto'
	})
})()

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
	static navigate(url, state = 'push') {
		__PHPSPA_RUNTIME_MANAGER__.__emit__('beforeload', { route: url })

		fetch(url, {
			headers: {
				'X-Requested-With': 'PHPSPA_REQUEST',
			},
			mode: 'cors',
			redirect: 'follow',
			keepalive: true,
		})
			.then(response => {
				response
					.text()
					.then(__res => {
						let __data

						if (__res && __res.trim().startsWith('{')) {
							try {
								__data = JSON.parse(__res)
							} catch (e) {
								__data = __res
							}
						} else {
							__data = __res || '' // Handle empty responses
						}

						// Emit success event
						__PHPSPA_RUNTIME_MANAGER__.__emit__('load', {
							route: url,
							success: true,
							error: false,
						})

						__call__(__data)
					})
					.catch(__ => __callError__(__))
			})
			.catch(__ => __callError__(__))

		function __callError__(__) {
			// Check if the error contains a response (e.g., HTTP 4xx/5xx with a body)
			if (__.response) {
				// Try extracting text/JSON from the error response
				__.response
					.text()
					.then(__fallbackRes => {
						let __data

						try {
							// If it looks like JSON, parse it
							__data = __fallbackRes.trim().startsWith('{')
								? JSON.parse(__fallbackRes)
								: __fallbackRes
						} catch (__parseError) {
							// Fallback to raw text if parsing fails
							__data = __fallbackRes
						}

						__PHPSPA_RUNTIME_MANAGER__.__emit__('load', {
							route: url,
							success: false,
							error: __.message || 'Server returned an error',
							data: __data, // Include the parsed/raw data
						})
						__call__(__data || '') // Pass the fallback data
					})
					.catch(() => {
						// Failed to read error response body
						__PHPSPA_RUNTIME_MANAGER__.__emit__('load', {
							route: url,
							success: false,
							error: __.message || 'Failed to read error response',
						})
						__call__('')
					})
			} else {
				// No response attached (network error, CORS, etc.)
				__PHPSPA_RUNTIME_MANAGER__.__emit__('load', {
					route: url,
					success: false,
					error: __.message || 'No connection to server',
				})
				__call__('')
			}
		}

		function __call__(__data) {
			if (
				'string' === typeof __data?.title ||
				'number' === typeof __data?.title
			) {
				document.title = __data.title
			}

			let __targetElement =
				document.getElementById(__data?.targetID) ??
				document.getElementById(history.state?.__targetID__) ??
				document.body

			__targetElement.innerHTML = __data?.content
				? atob(__data.content)
				: __data

			const __stateData = {
				__url__: url?.href ?? url,
				__title__: __data?.title ?? document.title,
				__targetID__: __data?.targetID ?? __targetElement.id,
				__content__: __data?.content ?? btoa(__data),
			}

			if (typeof __data['__reloadTime__'] !== 'undefined') {
				__stateData['__reloadTime__'] = __data.reloadTime
			}

			if (state === 'push') {
				__PHPSPA_RUNTIME_MANAGER__.__pushState__(
					__stateData,
					__stateData.__title__,
					url
				)
			} else if (state === 'replace') {
				__PHPSPA_RUNTIME_MANAGER__.__replaceState__(
					__stateData,
					__stateData.__title__,
					url
				)
			}

			let __hashedElement = document.getElementById(url?.hash?.substring(1))

			if (__hashedElement) {
				scroll({
					top: __hashedElement.offsetTop,
					left: __hashedElement.offsetLeft,
				})
			}

			__PHPSPA_RUNTIME_MANAGER__.__run_all__(__targetElement)

			if (typeof __data['__reloadTime__'] !== 'undefined') {
				setTimeout(phpspa.reloadComponent, __data.__reloadTime__)
			}
		}
	}

	/**
	 * Navigates back in the browser history.
	 *
	 * This static method calls `history.back()` to move the browser to the previous entry in the session history.
	 * The commented-out code suggests an intention to manage custom state and content restoration,
	 * but currently only the native browser history is used.
	 */
	static back() {
		history.back()
	}

	/**
	 * Navigates forward in the browser's session history.
	 *
	 * This static method calls the native `history.forward()` function to move the user forward by one entry in the session history stack.
	 *
	 * Note: The commented-out code suggests additional logic for handling custom state management and DOM updates, but it is currently inactive.
	 */
	static forward() {
		history.forward()
	}

	/**
	 * Reloads the current page by navigating to the current URL using the "replace" history mode.
	 * This does not add a new entry to the browser's history stack.
	 *
	 * @static
	 */
	static reload() {
		phpspa.navigate(new URL(location.href), 'replace')
	}

	/**
	 * Registers a callback function to be executed when the specified event is triggered.
	 *
	 * @param {string} event - The name of the event to listen for.
	 * @param {Function} callback - The function to call when the event is triggered.
	 */
	static on(event, callback) {
		if (!__PHPSPA_RUNTIME_MANAGER__.__events__[event]) {
			__PHPSPA_RUNTIME_MANAGER__.__events__[event] = []
		}
		__PHPSPA_RUNTIME_MANAGER__.__events__[event].push(callback)
	}

	/**
	 * Updates the application state by sending a custom fetch request and updating the DOM accordingly.
	 *
	 * @param {string} stateKey - The key representing the state to update.
	 * @param {*} value - The new value to set for the specified state key.
	 * @returns {Promise<void>} A promise that resolves when the state is updated and the DOM is modified, or rejects if an error occurs.
	 *
	 * @fires phpspa#beforeload - Emitted before the state is loaded.
	 *
	 * @example
	 * phpspa.setState('user', { name: 'Alice' })
	 *   .then(() => console.log('State updated!'))
	 *   .catch(err => console.error('Failed to update state:', err));
	 */
	static setState(key, value) {
		return new Promise((__resolve__, __reject__) => {
			let __currentScroll = {
				top: scrollY,
				left: scrollX,
			}

			const __url = new URL(location.href)
			const __json = JSON.stringify({ state: { key, value } })

			fetch(__url, {
				headers: {
					'X-Requested-With': 'PHPSPA_REQUEST',
					Authorization: `Bearer ${btoa(__json)}`,
				},
				mode: 'cors',
				redirect: 'follow',
				keepalive: true,
			})
				.then(__response => {
					__response
						.text()
						.then(__res => {
							let __data

							if (__res && __res.trim().startsWith('{')) {
								try {
									__data = JSON.parse(__res)
								} catch (__) {
									__data = __res
								}
							} else {
								__data = __res || '' // Handle empty responses
							}

							__resolve__()
							__call__(__data)
						})
						.catch(__ => {
							__reject__(__.message)
							__callError__(__)
						})
				})
				.catch(__ => {
					__reject__(__.message)
					__callError__(__)
				})

			function __callError__(__) {
				// Check if the error contains a response (e.g., HTTP 4xx/5xx with a body)
				if (__.response) {
					// Try extracting text/JSON from the error response
					__.response
						.text()
						.then(__fallbackRes => {
							let __data

							try {
								// If it looks like JSON, parse it
								__data = __fallbackRes.trim().startsWith('{')
									? JSON.parse(__fallbackRes)
									: __fallbackRes
							} catch (__parseError) {
								// Fallback to raw text if parsing fails
								__data = __fallbackRes
							}

							__call__(__data || '') // Pass the fallback data
						})
						.catch(() => {
							// Failed to read error response body
							__call('')
						})
				} else {
					// No response attached (network error, CORS, etc.)
					__call('')
				}
			}

			function __call__(__data) {
				if (
					'string' === typeof __data?.title ||
					'number' === typeof __data?.title
				) {
					document.title = __data.title
				}

				let __targetElement =
					document.getElementById(__data?.targetID) ??
					document.getElementById(history.state?.__targetID__) ??
					document.body

				__targetElement.innerHTML = __data?.content
					? atob(__data.content)
					: __data

				__PHPSPA_RUNTIME_MANAGER__.__run_all__(__targetElement)
				scroll(__currentScroll)
			}
		})
	}

	static reloadComponent() {
		const __currentScroll = {
			top: scrollY,
			left: scrollX,
		}

		fetch(new URL(location.href), {
			headers: {
				'X-Requested-With': 'PHPSPA_REQUEST',
			},
			mode: 'cors',
			redirect: 'follow',
			keepalive: true,
		})
			.then(__response => {
				__response
					.text()
					.then(__res => {
						let __data

						if (__res && __res.trim().startsWith('{')) {
							try {
								__data = JSON.parse(__res)
							} catch (__) {
								__data = __res
							}
						} else {
							__data = __res || '' // Handle empty responses
						}

						__call__(__data)
					})
					.catch(__ => {
						__callError__(__)
					})
			})
			.catch(__ => {
				__callError__(__)
			})

		function __callError__(__) {
			// Check if the error contains a response (e.g., HTTP 4xx/5xx with a body)
			if (__.response) {
				// Try extracting text/JSON from the error response
				__.response
					.text()
					.then(__fallbackRes => {
						let __data

						try {
							// If it looks like JSON, parse it
							__data = __fallbackRes.trim().startsWith('{')
								? JSON.parse(__fallbackRes)
								: __fallbackRes
						} catch (__) {
							// Fallback to raw text if parsing fails
							__data = __fallbackRes
						}

						__call__(__data || '') // Pass the fallback data
					})
					.catch(__ => {
						// Failed to read error response body
						__call__('')
					})
			} else {
				// No response attached (network error, CORS, etc.)
				__call__('')
			}
		}

		function __call__(__data) {
			if (
				'string' === typeof __data?.title ||
				'number' === typeof __data?.title
			) {
				document.title = __data.title
			}

			let __targetElement =
				document.getElementById(__data?.targetID) ??
				document.getElementById(history.state?.__targetID__) ??
				document.body

			__targetElement.innerHTML = __data?.content
				? atob(__data.content)
				: __data

			__PHPSPA_RUNTIME_MANAGER__.__run_all__(__targetElement)

			scroll(__currentScroll)

			if (typeof __data['reloadTime'] !== 'undefined') {
				setTimeout(phpspa.reloadComponent, __data.reloadTime)
			}
		}
	}

	static async __call(token, ...args) {
		const __url = new URL(location.href)
		const __json = JSON.stringify({ __call: { token, args } })

		try {
			const __response = await fetch(__url, {
				headers: {
					'X-Requested-With': 'PHPSPA_REQUEST',
					Authorization: `Bearer ${btoa(__json)}`,
				},
				mode: 'cors',
				redirect: 'follow',
				keepalive: true,
			})

			let __data
			const __res = await __response.text()

			if (__res && __res.trim().startsWith('{')) {
				try {
					__data = JSON.parse(__res)
					__data = __data['response'] ? atob(__data['response']) : __data
				} catch (__) {
					__data = __res
				}
			} else {
				__data = __res || '' // Handle empty responses
			}

			return __data
		} catch (__e) {
			// Check if the error contains a response (e.g., HTTP 4xx/5xx with a body)
			if (__e.response) {
				try {
					let __data
					const __fallbackRes = await __e.response.text()

					try {
						// If it looks like JSON, parse it
						__data = __fallbackRes.trim().startsWith('{')
							? JSON.parse(__fallbackRes)
							: __fallbackRes

						__data = __data['response']
							? atob(__data['response'])
							: __data
					} catch (__) {
						// Fallback to raw text if parsing fails
						__data = __fallbackRes
					}

					return __data
				} catch {
					// Failed to read error response body
					return ''
				}
			} else {
				// No response attached (network error, CORS, etc.)
				return ''
			}
		}
	} // end method
} // end class

/**
 * @class __PHPSPA_RUNTIME_MANAGER__
 *
 * @property {Object} __events__ - Internal event registry for custom event handling.
 *
 * @method __emit__
 * @static
 * @param {string} __event - The event name to emit.
 * @param {Object} __payload - The data to pass to event listeners.
 * @description Emits a custom event to all registered listeners.
 */
class __PHPSPA_RUNTIME_MANAGER__ {
	static __executed_scripts__ = new Set()

	static __executed_styles__ = new Set()

	/**
	 * Internal event registry for custom events.
	 * @type {Object}
	 * @private
	 */
	static __events__ = {
		beforeload: [],
		load: [],
	}

	static __run_all__(__container) {
		function __runInlineScripts__(__container) {
			const __scripts = __container.querySelectorAll('script')

			__scripts.forEach(__script => {
				const __content = btoa(__script.textContent.trim())

				if (
					!__PHPSPA_RUNTIME_MANAGER__.__executed_scripts__.has(__content)
				) {
					__PHPSPA_RUNTIME_MANAGER__.__executed_scripts__.add(__content)
					const __newScript = document.createElement('script')

					// Copy all attributes except data-type
					for (let __attr of __script.attributes) {
						__newScript.setAttribute(__attr.name, __attr.value)
					}

					// Check if original script has async attribute
					const __isAsync = __script.hasAttribute('async')

					if (__isAsync) {
						__newScript.textContent = `(async function() {\n${__script.textContent}\n})();`
					} else {
						__newScript.textContent = `(function() {\n${__script.textContent}\n})();`
					}

					document.head.appendChild(__newScript).remove()
				}
			})
		}

		function __runInlineStyles__(__container) {
			const __styles = __container.querySelectorAll('style')

			__styles.forEach(__style => {
				const __content = btoa(__style.textContent.trim())

				if (
					!__PHPSPA_RUNTIME_MANAGER__.__executed_styles__.has(__content)
				) {
					__PHPSPA_RUNTIME_MANAGER__.__executed_styles__.add(__content)
					const __newStyle = document.createElement('style')

					// Copy all attributes except data-type
					for (let __attr of __style.attributes) {
						__newStyle.setAttribute(__attr.name, __attr.value)
					}

					__newStyle.textContent = __style.textContent
					document.head.appendChild(__newStyle).remove()
				}
			})
		}

		__runInlineStyles__(__container)
		__runInlineScripts__(__container)
	}

	/**
	 * Emits an event, invoking all registered callbacks for the specified event.
	 *
	 * @param {string} __event - The name of the event to emit.
	 * @param {Object} __payload - The data to pass to each callback function.
	 */
	static __emit__(__event, __payload) {
		const __callbacks = __PHPSPA_RUNTIME_MANAGER__.__events__[__event] || []

		for (const __callback__ of __callbacks) {
			if (typeof __callback__ === 'function') {
				__callback__(__payload)
			}
		}
	}

	static __pushState__(...__state) {
		try {
			history.pushState(...__state)
		} catch (e) {}
	}

	static __replaceState__(...__state) {
		try {
			history.replaceState(...__state)
		} catch (e) {}
	}
}

if (typeof setState !== 'function') {
	function setState(stateKey, value) {
		return phpspa.setState(stateKey, value)
	}
}

if (typeof __call !== 'function') {
	function __call(functionName, ...args) {
		return phpspa.__call(functionName, ...args)
	}
}

;(function () {
	if (typeof window.phpspa === 'undefined') {
		window.phpspa = phpspa
	}
})()
