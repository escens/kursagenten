# Using Kursagenten with Custom Theme Templates

The Kursagenten plugin now supports using custom theme templates that follow WordPress standard template hierarchy. This gives you full control over header and footer while still being able to use the plugin's functionality.

## How it works

The plugin automatically checks if your theme has custom templates before using the plugin's default templates. If the theme has custom templates, those will be used instead.

## Supported template files

You can create the following template files in your theme:

### Single Course Template
- `single-ka_course.php` - For displaying single courses

### Taxonomy Templates
- `taxonomy-ka_course_location.php` - For course locations
- `taxonomy-ka_coursecategory.php` - For course categories  
- `taxonomy-ka_instructors.php` - For instructors
- `taxonomy-ka_course_location-{slug}.php` - Specific location (optional)
- `taxonomy-ka_coursecategory-{slug}.php` - Specific category (optional)
- `taxonomy-ka_instructors-{slug}.php` - Specific instructor (optional)

### Archive Template
- `archive-ka_course.php` - For course archive

## Example: Single Course Template

<?php get_header(); ?>
<?php kursagenten_get_content(); ?>
<?php get_footer(); ?>

Create `single-ka_course.php` in your theme:

```php
<?php
/**
 * Template for displaying single ka_course posts
 */

get_header();
?>

<?php
// Use the plugin's function to get course content
kursagenten_get_content();
?>

<?php
get_footer();
```

## Example: Taxonomy Template

Create `taxonomy-ka_course_location.php` in your theme:

```php
<?php
/**
 * Template for displaying ka_course_location taxonomy
 */

get_header();
?>

<?php
// Use the plugin's function to get taxonomy content
kursagenten_get_content();
?>

<?php
get_footer();
```

## Available functions

### `kursagenten_get_content()`
The main function that theme templates should use. It retrieves the plugin's content with the correct wrapper, but without header and footer (which the theme handles).

**Usage:**
```php
<?php kursagenten_get_content(); ?>
```

### Action Hooks

The plugin provides several action hooks that theme templates can use:

#### For Single Course (`single-ka_course.php`):
- `ka_singel_header_before` - Before the header section
- `ka_singel_header_after` - After the header section
- `ka_singel_content_before` - Before the content section
- `ka_singel_content_after` - After the content section
- `ka_singel_footer_before` - Before the footer section
- `ka_singel_footer_after` - After the footer section
- `ka_singel_after` - After the entire article

#### For Taxonomy Templates:
- `ka_taxonomy_header_before` - Before the header section
- `ka_taxonomy_header_after` - After the header section
- `ka_taxonomy_footer` - In the footer section
- `ka_taxonomy_after` - After the entire article

**Example hook usage:**
```php
<?php
get_header();

// Custom code before plugin content
do_action('ka_singel_header_before');

kursagenten_get_content();

// Custom code after plugin content
do_action('ka_singel_footer_after');

get_footer();
?>
```

## Important notes

1. **Header and Footer**: When using custom theme templates, it is your responsibility to call `get_header()` and `get_footer()`.

2. **Wrapper elements**: `kursagenten_get_content()` automatically includes necessary wrapper elements (`<div id="ka">`, etc.) that the plugin's CSS and JavaScript expect.

3. **Compatibility**: The plugin's hooks and functions work regardless of whether you use the plugin's templates or the theme's custom templates.

4. **Fallback**: If the theme doesn't have custom templates, the plugin automatically uses its own templates with header and footer.

## Design templates and list views

**IMPORTANT**: All design templates and list views you have configured in the plugin settings will work as usual, even when using custom theme templates!

### How it works:

- **Design templates**: When `kursagenten_get_content()` is called, it automatically retrieves the correct design template based on your settings:
  - Single course: The `kursagenten_single_design` setting is respected
  - Taxonomy: The `kursagenten_taxonomy_design` or taxonomy-specific settings are respected
  - Archive: The `kursagenten_archive_design` setting is respected

- **List views**: List views (standard, grid, compact, etc.) work as usual:
  - Archive: The `kursagenten_archive_list_type` setting is respected
  - Taxonomy: The `kursagenten_taxonomy_list_type` or taxonomy-specific settings are respected

- **CSS and JavaScript**: The plugin's CSS and JavaScript are automatically loaded based on WordPress conditional tags (`is_singular()`, `is_tax()`, etc.), regardless of which template is used. This means all design templates get the correct styling.

- **Layout settings**: Layout settings (default, full-width, etc.) are also respected, even when theme templates are used.

### Example:

If you have set:
- Single course design to "modern"
- Taxonomy list type to "grid" 
- Taxonomy design to "default"

Then these settings will automatically be used when theme templates call `kursagenten_get_content()`. You don't need to do anything extra!

## Troubleshooting

If header and footer don't appear:
- Check that you're calling `get_header()` and `get_footer()` in the theme template
- Check that the template filename is correct (e.g., `single-ka_course.php`)
- Check that the template file is located in the root folder of your theme

If plugin content doesn't appear:
- Check that you're calling `kursagenten_get_content()` in the theme template
- Check that the plugin's CSS and JavaScript are loading (they load automatically when plugin functions are used)
