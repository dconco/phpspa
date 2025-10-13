# Welcome to phpspa! 👋

Ready to build modern, reactive web UIs without leaving the comfort of PHP? You're in the right place.

`phpspa` is a **component-based library** that brings the power and syntax of React to your PHP workflow. You build small, reusable components, and the library handles the magic of rendering them and updating the UI when their state changes.

This documentation is designed to be fast and straight to the point. No long stories, just code. Let's dive in and start building something awesome. 🚀

Got it. An installation guide is the perfect next step.

Here is the documentation page for **Installation**.

-----

## 🚀 Installation

Getting `phpspa` up and running is simple. You have two options: use our pre-configured template for a new project, or install the library into an existing one.

---

### Using the Template (Recommended)

This is the quickest way to start a new project. It sets up the entire project structure for you.

**Step 1: Create the Project**

Run the following command to clone the template into a new directory.

```bash
composer create-project phpspa/phpspa my-app
```

**Step 2: Navigate to Your Project**

```bash
cd my-app
```

**Step 3: Start the Development Server**

The template comes with a built-in PHP development server.

```bash
composer start
```

That's it\! Your `phpspa` application is now running and ready for you to start building. ✨

-----

### Starting From Scratch

If you want to integrate `phpspa` into an existing project, you can install it directly with Composer.

```bash
composer require dconco/phpspa
```

You will then need to set up your own layout and `index.php` file to initialize the `App` class.
