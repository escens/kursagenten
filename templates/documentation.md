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
