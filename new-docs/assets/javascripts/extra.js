// Enhanced functionality for phpSPA documentation

document.addEventListener("DOMContentLoaded", function () {
   // Add copy buttons to code blocks
   addCopyButtons();

   // Add tooltips for glossary terms
   addGlossaryTooltips();

   // Add smooth scrolling for anchor links
   addSmoothScrolling();

   // Add interactive elements
   addInteractiveElements();

   // Add search enhancements
   enhanceSearch();
});

function addCopyButtons() {
   const codeBlocks = document.querySelectorAll("pre code");

   codeBlocks.forEach(function (codeBlock) {
      const button = document.createElement("button");
      button.className = "copy-button";
      button.textContent = "Copy";
      button.setAttribute("data-md-tooltip", "Copy to clipboard");

      button.addEventListener("click", function () {
         navigator.clipboard.writeText(codeBlock.textContent).then(function () {
            button.textContent = "Copied!";
            button.classList.add("copied");

            setTimeout(function () {
               button.textContent = "Copy";
               button.classList.remove("copied");
            }, 2000);
         });
      });

      const pre = codeBlock.parentElement;
      if (pre.tagName === "PRE") {
         pre.style.position = "relative";
         pre.appendChild(button);
      }
   });
}

function addGlossaryTooltips() {
   const glossaryTerms = {
      component:
         "A reusable piece of UI that encapsulates HTML, logic, and styling",
      state: "Data that can change over time and triggers re-renders when updated",
      route: "A URL pattern that maps to a specific component",
      SPA: "Single Page Application - a web app that loads once and updates dynamically",
      CSRF: "Cross-Site Request Forgery - a security vulnerability that phpSPA protects against",
      SSR: "Server-Side Rendering - generating HTML on the server before sending to client",
   };

   Object.keys(glossaryTerms).forEach(function (term) {
      const regex = new RegExp(`\\b${term}\\b`, "gi");
      const walker = document.createTreeWalker(
         document.body,
         NodeFilter.SHOW_TEXT,
         null,
         false
      );

      const textNodes = [];
      let node;
      while ((node = walker.nextNode())) {
         if (node.nodeValue.match(regex)) {
            textNodes.push(node);
         }
      }

      textNodes.forEach(function (textNode) {
         const parent = textNode.parentElement;
         if (parent && !parent.classList.contains("glossary-term")) {
            const newHTML = textNode.nodeValue.replace(
               regex,
               `<span class="glossary-term" title="${glossaryTerms[term]}">$&</span>`
            );
            const wrapper = document.createElement("span");
            wrapper.innerHTML = newHTML;
            parent.replaceChild(wrapper, textNode);
         }
      });
   });
}

function addSmoothScrolling() {
   const links = document.querySelectorAll('a[href^="#"]');

   links.forEach(function (link) {
      link.addEventListener("click", function (e) {
         const targetId = this.getAttribute("href").substring(1);
         const targetElement = document.getElementById(targetId);

         if (targetElement) {
            e.preventDefault();
            targetElement.scrollIntoView({
               behavior: "smooth",
               block: "start",
            });

            // Update URL without triggering scroll
            history.pushState(null, null, `#${targetId}`);
         }
      });
   });
}

function addInteractiveElements() {
   // Add interactive code examples
   const interactiveExamples = document.querySelectorAll(
      ".interactive-example"
   );

   interactiveExamples.forEach(function (example) {
      const runButton = document.createElement("button");
      runButton.textContent = "Run Example";
      runButton.className = "run-example-button";

      runButton.addEventListener("click", function () {
         // Simulate running the example
         const output = example.querySelector(".example-output");
         if (output) {
            output.style.display = "block";
            output.innerHTML = "<p>✅ Example executed successfully!</p>";
         }
      });

      example.appendChild(runButton);
   });

   // Add expandable sections
   const expandableSections = document.querySelectorAll(".expandable");

   expandableSections.forEach(function (section) {
      const header = section.querySelector(".expandable-header");
      const content = section.querySelector(".expandable-content");

      if (header && content) {
         header.style.cursor = "pointer";
         header.addEventListener("click", function () {
            const isExpanded = content.style.display !== "none";
            content.style.display = isExpanded ? "none" : "block";

            const icon = header.querySelector(".expand-icon");
            if (icon) {
               icon.textContent = isExpanded ? "▶" : "▼";
            }
         });
      }
   });
}

function enhanceSearch() {
   const searchInput = document.querySelector(
      '[data-md-component="search-query"]'
   );

   if (searchInput) {
      // Add search suggestions
      const suggestions = [
         "components",
         "routing",
         "state management",
         "installation",
         "CSRF protection",
         "performance",
         "examples",
      ];

      searchInput.addEventListener("input", function () {
         const query = this.value.toLowerCase();

         if (query.length > 0) {
            const matches = suggestions.filter((s) => s.includes(query));

            if (matches.length > 0) {
               // Show suggestions (simplified implementation)
               console.log("Suggestions:", matches);
            }
         }
      });
   }
}

// Add keyboard shortcuts
document.addEventListener("keydown", function (e) {
   // Ctrl+K or Cmd+K to focus search
   if ((e.ctrlKey || e.metaKey) && e.key === "k") {
      e.preventDefault();
      const searchInput = document.querySelector(
         '[data-md-component="search-query"]'
      );
      if (searchInput) {
         searchInput.focus();
      }
   }

   // Escape to close search
   if (e.key === "Escape") {
      const searchInput = document.querySelector(
         '[data-md-component="search-query"]'
      );
      if (searchInput && document.activeElement === searchInput) {
         searchInput.blur();
      }
   }
});

// Add progress indicator for long pages
function addProgressIndicator() {
   const progressBar = document.createElement("div");
   progressBar.className = "reading-progress";
   progressBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(90deg, var(--md-primary-fg-color), var(--md-accent-fg-color));
        z-index: 1000;
        transition: width 0.1s ease;
    `;

   document.body.appendChild(progressBar);

   window.addEventListener("scroll", function () {
      const scrollTop = window.pageYOffset;
      const docHeight = document.body.scrollHeight - window.innerHeight;
      const scrollPercent = (scrollTop / docHeight) * 100;

      progressBar.style.width = Math.min(scrollPercent, 100) + "%";
   });
}

// Initialize progress indicator
addProgressIndicator();

// Add table of contents enhancement
function enhanceTableOfContents() {
   const tocLinks = document.querySelectorAll(".md-nav__link");

   tocLinks.forEach(function (link) {
      link.addEventListener("click", function () {
         // Remove active class from all links
         tocLinks.forEach((l) => l.classList.remove("active"));

         // Add active class to clicked link
         this.classList.add("active");
      });
   });
}

enhanceTableOfContents();

// Add theme toggle enhancement
function enhanceThemeToggle() {
   const themeToggle = document.querySelector('[data-md-component="palette"]');

   if (themeToggle) {
      themeToggle.addEventListener("change", function () {
         // Add smooth transition when switching themes
         document.body.style.transition =
            "background-color 0.3s ease, color 0.3s ease";

         setTimeout(function () {
            document.body.style.transition = "";
         }, 300);
      });
   }
}

enhanceThemeToggle();
