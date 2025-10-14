# 🚀 Installation

<p style="font-size: 1.2rem; color: var(--md-default-fg-color--light); margin-bottom: 2rem;">
Getting PhpSPA up and running is simple. You have two options: use our pre-configured template for a new project, or install the library into an existing one.
</p>

---

## Using the Template (Recommended)

!!! success "Quick Setup"
    This is the quickest way to start a new project. It sets up the entire project structure for you.

> Step 1: Create the Project

Run the following command to clone the template into a new directory.

```bash
composer create-project phpspa/phpspa my-app
```

> Step 2: Navigate to Your Project

```bash
cd my-app
```

> Step 3: Start the Development Server

The template comes with a built-in PHP development server.

```bash
composer start
```

!!! check "That's it!"
    Your PhpSPA application is now running and ready for you to start building. ✨

---

## Starting From Scratch

!!! note "Existing Projects"
    If you want to integrate PhpSPA into an existing project, you can install it directly with Composer.

```bash
composer require dconco/phpspa
```

!!! warning "Additional Setup Required"
    You will then need to set up your own layout and `index.php` file to initialize the `App` class.
