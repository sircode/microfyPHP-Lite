## microfy.php

**Minimal utility helpers for everyday PHP tasks**

Status: EXPERIMENTAL

---

### 🧰 What is it?

`microfy.php` is a lightweight collection of procedural PHP helper functions designed to **speed up development** and simplify common patterns like superglobal access, debugging, logging, array handling, UI snippets, and database access.

Forget bloated frameworks — `microfy.php` gives you practical tools with no setup, no classes, no magic.

---

### 💡 Why use it?

* You’re tired of writing the same boilerplate over and over.
* You want quick access to `$_GET`, `$_POST`, debug dumps, and simple logs.
* You like readable, testable, no-dependency PHP.
* You value control and minimalism over "magic".

---

### ✨ Features

* **Request Shortcuts**: `get()`, `post()`, `_request()`, plus extract helpers.
* **Debug Helpers**: `pp()`, `pd()`, `d()`, `log_pr()`, `log_vd()`, `mlog()`.
* **Slugify**: Clean URL-friendly slugs from text.
* **JSON Read**: `jsonf()` for quick file-based config/data.
* **UI HTML Snippets**: `h()`, `br()`, `hr()`, `mark()`, `code()`, `a()`, `html_table()` etc.
* **Array Utils**: `get_r()` for safe access.
* **Database**: `db_pdo()`, `db_all()`, `db_exists()` — simple and safe.
* **Auto Titles + Lists**: `c_str()`, `c_list()` for numbered docs or steps.

---

### 📌 When to Use microfy.php

Use `microfy.php` when you:

* Build custom admin tools, prototypes, dashboards, or internal apps.
* Need small enhancements, not full-stack frameworks.
* Prefer writing straight PHP with expressive shortcuts.

---

### ⚙️ Usage

1. Drop `microfy.php` into your project.

2. Include it:

   ```php
   require_once 'microfy.php';
   ```

3. Use any helper you need:

   ```php
   $name = _get('name', 'guest');
   pp(['Hello' => $name]);
   log_vd($_SESSION, 'Session Data');
   ```

---

### 🧪 Examples

```php
// Request value with default
$lang = _get('lang', 'en');

// Pretty Print array and halt
ppd($_POST);

// Log structured array with label
log_pr($data, 'Form Submission');

// Create HTML link
echo a('example.com', 'Visit', '_blank', 'btn');

// Database connect + fetch
$pdo = db_pdo('localhost', 'mydb', 'user', 'pass');
$data = db_all($pdo, 'SELECT * FROM users');
ul(array_column($data, 'username'));
```

🧪 [More Examples](https://itnb.com/microfy/)

---


## 🤝 Contributing

We welcome contributions! See [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

### 🔒 License

**MIT License** — © 2024–2025 [SirCode](https://itnb.com/) |
This project is not affiliated with or endorsed by the PHP Foundation.
Use at your own risk — no warranties, no guarantees, just useful code.

---

### 📦 Also Available as Object-Oriented Version

If you prefer a **class-based approach**, check out
👉 [`MicrofyClass.php`](https://github.com/sircode/MicrofyClass.php) — same helper functions, accessible via `Microfy::`.

---

---
