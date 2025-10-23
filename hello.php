<?php
echo "Hello! PHP is working!";
?>
```

Upload it and visit: `https://jobs.gotoaus.com/hello.php`

**If this ALSO gives 500 error**, the problem is NOT your code - it's server configuration!

### Step 2: Check .htaccess file

The **.htaccess** file might be causing issues. Do you have a .htaccess file? If yes, **temporarily RENAME it** to `.htaccess.bak` and try again.

### Step 3: Check Error Logs in cPanel

1. Go to cPanel
2. Find **Errors** or **Error Log**
3. Look at the LAST error
4. **Send me a screenshot** of the error log

### Step 4: Check PHP Version

In cPanel:
1. Go to **Select PHP Version** or **MultiPHP Manager**
2. Make sure PHP version is **7.4 or 8.0+**
3. Screenshot this page too

### Most Likely Causes:

1. **Wrong PHP version** (needs 7.0+)
2. **Missing PHP extensions**
3. **Bad .htaccess file**
4. **File encoding issues** (must be UTF-8 without BOM)
5. **Syntax error in config.php**

### Quick Fix: Check your config.php file

Open config.php in a text editor and check:
- Is there ANYTHING before `<?php`? (even a space will break it!)
- Are all the quotes matching?
- Does it end with `?>`?

**What you should see at the START of config.php:**
```
<?php
```

**NOT:**
```
 <?php    (space before)
﻿<?php    (invisible BOM character)