# Kursagenten templating system

## Directory Structure
```
templates/
├── designs/            # Design variations for different views
│   ├── single/         # Single course page designs
│   ├── taxonomy/       # Taxonomy page designs
│   └── archive/        # Archive page designs
├── list-types/         # Course listing display types, included in archives and taxonomy designs.
│   ├── grid.php        # Grid view template
│   ├── standard.php    # Standard list view template
│   └── compact.php     # Compact list view template
├── layouts/            # Use theme standard width or force full width
│   ├── default.php     # Theme width
│   ├── full-width.php  # Full width
```

## Template Loading System

The template system uses a hierarchical approach where designs and layouts are loaded based on admin settings in `coursedesign.php`. The system follows this loading order:

1. Base styles (`frontend-course-style.css`)
2. Design specific styles (`design-{type}-{design}.css`)
3. List type specific styles (`list-{type}.css`)

## Creating New Templates

### Adding a New List Type
1. Create new template file in `templates/list-types/`:
   ```php
   // templates/list-types/new-type.php
   ```

2. Add corresponding CSS file:
   ```css
   // frontend/css/list-new-type.css
   ```

3. Add to options in `coursedesign.php`:
   ```php
   $list_types = [
       'standard' => 'Standard liste',
       'grid' => 'Rutenett',
       'compact' => 'Kompakt liste',
       'new-type' => 'New Type Name'  // Add this line
   ];
   ```

### Adding a New Design
1. Create design files for type:
   ```
   templates/designs/single/new-design.php/
   templates/designs/archive/new-design.php/
   templates/designs/taxonomy/new-design.php/
   ```

   Main html structure
   All template files are wrapped in "main", in layout default or full width
   ```html
   <main id="ka-main" class="kursagenten-wrapper" role="main"> <!--remove, is added in /in layout/ default or full width-->
   </main>
   ```
   This is the structure to use in new template/design files
   ```html
   <article class="ka-outer-container course-container">
      <header class="ka-section ka-header">
         <div class="header-title">
            <h1>Title</h1>
         </div>
      </header>
      <section class="ka-section ka-main-content">
         <div class="ka-content-container describe-content-with-class">
            <div class="course-grid"></div>
         </div>
      </section>
   </article>
   ```

2. Add CSS files:
   ```
   frontend/css/design-single-new-design.css
   frontend/css/design-archive-new-design.css
   frontend/css/design-taxonomy-new-design.css
   ```

3. Add to options in `coursedesign.php`:
   ```php
   $designs = [
       'default' => 'Standard',
       'modern' => 'Moderne',
       'minimal' => 'Minimal',
       'new-design' => 'New Design Name'  // Add this line
   ];
   ```

## Style Integration
All designs must follow these integration points:

1. Base Styles: Always extend from `frontend-course-style.css`
2. List Types: Must include required HTML structure from base templates
3. Design Variations: Modify appearance. Base it on default.php template.

## Required Template Files
For each new design, ensure you have:

1. Template files:
   - designs/single/`default.php` for single course view
   - designs/archive/`default.php` for course listing pages (archives)
   - designs/taxonomy/`default.php` for category/location pages
   - list-types/`standard.php` for category/location pages

2. Style files:
   - Base CSS for the design
   - Responsive styles
   - List type specific modifications

## CSS Loading Order
The system automatically handles CSS loading based on selected options:

1. Base styles are always loaded first
2. List type styles are loaded second
3. Design-specific styles are loaded last

This allows for proper cascading and overriding of styles while maintaining consistency.


# Kursagenten Course Filtering System Documentation

## Overview
The course filtering system is a complex implementation that handles course filtering, sorting, and visibility management in the Kursagenten WordPress plugin. This documentation covers the main components and their interactions.

## Core Components

### 1. Archive Template (`archive-course.php`)
The main template for displaying course listings with filtering capabilities.

#### Key Features:
- Dynamic filter configuration through WordPress options
- Top and left filter columns
- Chip and list-based filter types
- Real-time AJAX filtering
- Sorting functionality
- Pagination support

#### Filter Configuration Options:
```php
$top_filters = get_option('kursagenten_top_filters', []);
$left_filters = get_option('kursagenten_left_filters', []);
$filter_types = get_option('kursagenten_filter_types', []);
$available_filters = get_option('kursagenten_available_filters', []);
```

### 2. AJAX Filtering (`course-ajax-filter.js`)

#### Main Functions:
1. **Filter Management**
   ```javascript
   updateFiltersAndFetch(newFilters)
   ```
   - Handles filter updates and AJAX requests
   - Updates URL parameters
   - Manages active filter display

2. **Sorting Implementation**
   ```javascript
   initializeSorting()
   ```
   - Handles sort options: title, price, date
   - Maintains sort state in URL parameters

3. **Filter Types**
   - Chip filters: Single-select with visual feedback
   - List filters: Multi-select with checkbox interface
   - Search filter: Real-time text search
   - Date filter: Range selection with calendar interface (not implemented completely)

#### URL Parameter Mapping:
```javascript
const filterKeyMap = {
    'language': 'sprak',
    'locations': 'sted',
    'instructors': 'i',
    'categories': 'k'
};
```

### 3. Server-Side Processing (`course-ajax-filter.php`)

#### Main Handler:
```php
function filter_courses_handler()
```

#### Features:
- Security verification with nonce
- Dynamic query building based on filter parameters
- Support for multiple taxonomy and meta queries
- Custom sorting implementation
- Pagination handling

#### Query Parameters:
- **Taxonomies**: coursecategory, course_location, instructors
- **Meta Fields**: course_language, course_first_date
- **Search**: Supports text search across all fields

### 4. Query Management (`queries.php`)

#### Key Functions:

1. **Course Date Query**
   ```php
   function get_course_dates_query($args = [])
   ```
   - Main query builder for course listings
   - Handles date sorting and visibility filtering
   - Supports pagination

2. **Course Data Retrieval**
   ```php
   function get_selected_coursedate_data($related_coursedate)
   function get_all_sorted_coursedates($related_coursedate)
   ```
   - Retrieves and sorts course date information
   - Handles missing dates and visibility rules

### 5. Visibility Management (`visibility_management.php`)

#### Hidden Terms Configuration:
```php
define('KURSAG_HIDDEN_TERMS', serialize([
    'skjult', 'skjul', 'usynlig', 'inaktiv', 'ikke-aktiv'
]));
```

#### Integration Points:
1. **Query Modification**
   ```php
   function exclude_hidden_kurs_posts($query)
   ```
   - Modifies main query to exclude hidden posts
   - Runs early in the query process (priority 5)

2. **Term Filtering**
   ```php
   function exclude_hidden_terms_from_list($args, $taxonomies)
   ```
   - Filters out hidden terms from category lists
   - Affects dropdown menus and filter options

## Query Flow and Visibility Integration

1. **Initial Query Filter**
   - `visibility_management.php` hooks into `pre_get_posts`
   - Excludes posts with hidden terms
   - Affects all main queries

2. **AJAX Filter Processing**
   - Filter request received
   - Visibility rules applied through `modify_course_dates_query`
   - Additional filters applied
   - Results returned with visibility rules intact

3. **Course Data Retrieval**
   - Visibility checked in `get_selected_coursedate_data`
   - Hidden terms verified in `has_hidden_terms`
   - Results filtered before return

## Development Guidelines

### Adding New Filters

1. Add filter configuration to WordPress options:
   ```php
   update_option('kursagenten_available_filters', [
       'new_filter' => [
           'label' => 'New Filter',
           'placeholder' => 'Select Option'
       ]
   ]);
   ```

2. Add URL parameter mapping in JavaScript:
   ```javascript
   filterKeyMap['new_filter'] = 'url_key';
   ```

3. Update query handling in `filter_courses_handler`

### Modifying Visibility Rules

1. Update `KURSAG_HIDDEN_TERMS` constant
2. Modify `exclude_hidden_kurs_posts` if needed
3. Update admin UI in `visibility_management.php`

## Common Issues and Solutions

1. **Hidden Posts Still Visible**
   - Verify terms are in `KURSAG_HIDDEN_TERMS`
   - Check query priority in `pre_get_posts`
   - Verify taxonomy assignments

2. **Filter Not Working**
   - Check JavaScript console for errors
   - Verify AJAX nonce
   - Check URL parameter mapping
   - Verify query construction

3. **Sort Issues**
   - Verify meta key existence
   - Check sort parameter handling
   - Verify date format consistency

## Performance Considerations

1. **Query Optimization**
   - Use proper indexing on meta fields
   - Limit post count per page
   - Cache frequently used queries

2. **JavaScript Performance**
   - Debounce filter updates
   - Minimize DOM operations
   - Use event delegation

3. **Visibility Checks**
   - Cache term lists where possible
   - Use proper taxonomy indexing
   - Minimize redundant visibility checks

## Security Implementation

1. **AJAX Security**
   - Nonce verification
   - Capability checks
   - Input sanitization

2. **Query Safety**
   - Prepared statements
   - Sanitized meta queries
   - Escaped output

## Future Development

1. **Planned Improvements**
   - Enhanced caching system
   - Additional filter types
   - Advanced sorting options
   - Performance optimizations

2. **Integration Points**
   - Filter hook documentation
   - Action hook documentation
   - Extension possibilities

