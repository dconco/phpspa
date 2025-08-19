// MathJax configuration for phpSPA documentation
window.MathJax = {
   tex: {
      inlineMath: [
         ["$", "$"],
         ["\\(", "\\)"],
      ],
      displayMath: [
         ["$$", "$$"],
         ["\\[", "\\]"],
      ],
      processEscapes: true,
      processEnvironments: true,
   },
   options: {
      ignoreHtmlClass: "tex2jax_ignore",
      processHtmlClass: "tex2jax_process",
   },
};

// Load MathJax dynamically if needed
document.addEventListener("DOMContentLoaded", function () {
   const mathElements = document.querySelectorAll(".math, .arithmatex");

   if (mathElements.length > 0) {
      const script = document.createElement("script");
      script.src =
         "https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js";
      script.async = true;
      document.head.appendChild(script);
   }
});
