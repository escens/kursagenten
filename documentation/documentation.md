# Kursagenten WordPress Plugin

## Table of Contents
1. [Overview](#overview)
2. [Installation](#installation)
3. [Structure](#structure)
4. [Functionality](#functionality)
5. [API Integration](#api-integration)
6. [Custom Post Types](#custom-post-types)
7. [Hooks and Filters](#hooks-and-filters)
8. [Frontend](#frontend)
9. [Administration](#administration)
10. [Troubleshooting](#troubleshooting)
11. [Performance](#performance)

## Overview
Kursagenten is a WordPress plugin that integrates course data from the Kursagenten API, enabling display and management of courses on WordPress websites. The plugin handles automatic synchronization (pull) of course data, frontend display, and provides administrative tools for course content management.

## Installation
1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress admin
3. Configure API keys under Settings > Kursagenten
4. Run initial course data synchronization

## Structure
```
kursagenten/
├── assets/
│   ├── css/
│   │   ├── components/
│   │   ├── layouts/
│   │   └── modules/
│   ├── js/
│   │   ├── admin/
│   │   │   ├── components/
│   │   │   └── modules/
│   │   └── frontend/
│   │       ├── components/ 
│   │       └── modules/
│   ├── icons/
│   │   ├── admin/
│   │   └── frontend/
│   ├── includes/
│   │   ├── api/
│   │   │   ├── endpoints/
│   │   │   ├── controllers/
│   │   │   └── models/
│   │   ├── admin/
│   │   │   ├── views/
│   │   │   ├── controllers/
│   │   │   └── helpers/
│   │   └── frontend/
│   │       ├── widgets/
│   │       ├── shortcodes/
│   │       └── blocks/
│   ├── templates/
│   │   ├── partials/
│   │   │   ├── course/
│   │   │   └── forms/
│   │   ├── admin/
│   │   └── emails/
│   └── languages/
│       ├── nb_NO/
│       ├── nn_NO/
│       └── en_US/
```

## Functionality

### Core Features
- Automatic synchronization with Kursagenten API
- Custom post types for courses with custom fields
- Registration system integrated with Kursagenten
- Webhook handling for real-time updates
- Multilingual support (coming)
- Customizable frontend display
- Advanced course search and filtering system
- AJAX-powered course filtering
- Responsive design with modern UI components

### Course Administration
- Manual and automatic course synchronization (pull data)
- Additional course information editing.
- Course category and tag management (limited)
- Filter selection
- Custom course templates and layouts

## API Integration

### Kursagenten API
- REST API endpoints for course data
- API key authentication not necessary, only pulls data
- Automatic synchronization via cron jobs
- Error handling and logging
- Real-time webhook integration

### Webhooks
- Real-time update reception
- Webhook signature validation
- Handling of various webhook events:
  - New courses
  - Updates
  - Deletions
  - Registrations

## Admin options pages

### Sync and cleanup buttons

Cleanup button:
AJAX call: When the button is clicked, it sends an AJAX call to the cleanup_courses action
Calls cleanup_courses_on_demand(): This function performs a comprehensive cleanup of the entire system
Deletes old courses: Removes courses that no longer exist in the API
Deletes old course dates: Removes course dates that no longer exist in the API
Cleans up locations: Deletes specific_locations metadata

## Custom Post Types

### Course (course)
```php
'supports' => [
    'title',
    'editor',
    'thumbnail',
    'excerpt',
    'custom-fields'
]
```
#### Description
Courses are created as pages, with a parent course, and subpages for course locations if more than one location exist.

#### Meta Fields CPT course
- location_id: Unique location ID from Kursagenten (integer)
- course_content: Course description (sanitized HTML)
- course_price: Course price (integer)
- course_text_before_price: Text displayed before price amount
- course_text_after_price: Text displayed after price amount
- course_difficulty_level: Course difficulty level
- course_type: Course type (from courseTypes array)
- course_is_online: Whether the course is online (boolean)
- course_municipality: Municipality where the course is held
- course_county: County where the course is held
- course_language: Course language
- course_external_sign_on: External registration page URL
- course_contactperson_name: Contact person's name
- course_contactperson_phone: Contact person's phone number
- course_contactperson_email: Contact person's email (sanitized)
- meta_description: Course introtext (sanitized)

#### Taxonomies
- ka_coursecategory (tidligere coursecategory): Categories for courses. In Kursagenten: tags
- ka_course_location (tidligere course_location): Locations are municipalities, or county if municiplality doesn't exist
- ka_instructors (tidligere instructors): Instructor name

### Course Date (coursedate)
```php
'supports' => [
    'title',
    'custom-fields'
]
```

#### Description
Course dates are created as seperate posts, representing specific scheduled instances of a course (location + specific date). They are connected to course by field "course_related_course". Course dates are only used in course pages and archive pages for course, ie course calendar (/kurs/).

#### Meta Fields CPT coursedate
- main_course_id: Reference to "parent" course ID
- location_id: Location ID from Kursagenten
- schedule_id: Schedule ID from Kursagenten
- course_title: Course name/title
- course_first_date: Start date of the course (formatted as d.m.Y)
- course_last_date: End date of the course (formatted as d.m.Y)
- course_registration_deadline: Last date for registration (formatted as d.m.Y)
- course_duration: Duration of the course
- course_time: Course time details
- course_time_type: Type of course timing (day/evening etc)
- course_start_time: Course start time
- course_end_time: Course end time
- course_price: Course price (integer)
- course_text_before_price: Text displayed before price
- course_text_after_price: Text displayed after price
- course_code: Course reference code
- course_button_text: Custom text for registration button
- course_language: Course language
- course_location_room: Specific room at location
- course_maxParticipants: Maximum number of participants
- course_showRegistrationForm: Whether to display registration form (boolean)
- course_markedAsFull: Manually marked as full (boolean)
- course_isFull: Automatically calculated full status (boolean)
- course_signup_url: URL for course registration
- course_location: Course location name
- course_location_freetext: Additional location description
- course_address_street: Street name
- course_address_street_number: Street number
- course_address_zipcode: Postal code
- course_address_place: City/Place name

#### Taxonomies
- ka_coursecategory (tidligere coursecategory): Categories for courses. In Kursagenten: tags
- ka_course_location (tidligere course_location): Locations are municipalities, or county if municiplality doesn't exist
- ka_instructors (tidligere instructors): Instructor name

### Instructor (instructor)
```php
'supports' => [
    'title',
    'editor',
    'excerpt',
    'thumbnail',
    'custom-fields'
]
```

#### Description
Instructors are created as separate posts, representing course instructors. They are automatically created and updated when syncing courses from Kursagenten API. Custom fields are added, as there is limited information provided in Kursagenten. Instructors are connected to courses and course dates through the field "course_related_course", and courses/dates are connected to instructors through "course_related_instructor".

#### Meta Fields CPT instructor
- course_instructor_id: Unique instructor ID from Kursagenten
- course_instructor_firstname: Instructor's first name
- course_instructor_lastname: Instructor's last name
- course_instructor_email: Instructor's email address (sanitized)
- course_instructor_phone: Instructor's phone number
- course_related_course: Array of related course IDs
- meta_description: Instructor introduction text (sanitized)

#### Taxonomies
- ka_coursecategory (tidligere coursecategory): Categories the instructor teaches
- ka_course_location (tidligere course_location): Locations where the instructor teaches

## Taxonomies

### Course category (ka_coursecategory)

#### Description
Categories are collected from Kursagenten. In Kursagenten they are called tags, and are custom strings created by course provider. They are added to all CPT's.

### Course location (ka_course_location)

#### Description
Location is collected from Kursagenten. They are municipalities, or county if municiplality doesn't exist. It is added to all CPT's.

### Instructors (ka_instructors)

#### Description
I wanted instructors to be searchable in WP, and also to use them for filtering. I was told taxonomies where best for filters, and CPT for search. They are added to all CPT's.


## Frontend

### Shortcodes
```php
[kursliste] // Display course list
...more coming
```

### Templates
- archive-course.php: Course archive/listing
- single-course.php: Single course display
- archives and singles for instructor, course location and course category will be created

### Template parts
- coursedates_default.php: Course archive/listing
In admin options page it will be possible to change course list layout. More layout are comming.

### Customization
- Theme compatibility
- CSS variables for styling
- Responsive design
- Customizable templates
- AJAX-powered filtering system
- Modern grid-based layouts

## Administration

### Settings
- Provider information: Company name, address, social profiles ie. (bedriftsinformasjon)
- Course settings: Course sync, image placeholders, and API connection settings (kursinnstilinger)
- Display options: Course layouts, filter settings, colors and fonts (comming) (kursdesign)
- URL's: Change slug for course, instructor, course category and course location (endre url-er)
- Advanced options: Misc useful functionality

### Tools
- Manual synchronization
- Debug logging
- Cache management
- Course visibility management
- Filter configuration

### Security
- API key validation
- Webhook signature verification
- User role management
- Data sanitization
- Nonce verification

## Troubleshooting

### Common Issues
1. API connection errors
2. Synchronization issues
3. Webhook errors
4. Display problems
5. Filter functionality

### Logging
- Error logging in wp-content/debug.log
- API response logging
- Synchronization log
- Registration log
- Performance monitoring

## Performance

### Caching
- Transient API for course data
- Object caching for search results
- Page caching compatibility
- API response caching

### Optimization
- Lazy loading of images
- Minified resources
- Database indexing
- AJAX-powered filtering
- Efficient database queries
- Resource bundling