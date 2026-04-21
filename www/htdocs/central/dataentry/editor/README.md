# New Data Entry Rendering Engine (ABCD)

**Author:** Roger C. Guilherme  
**Date:** April 19, 2026  

This directory contains the modular classes extracted from the `dibujarhojaentrada.php` monolith. This refactoring marks a major milestone in the modernization of the ABCD system, transitioning from a procedural 2000-line script to a scalable, component-based architecture.

## Architecture & Structure

- **loader.php**: The main entry point. It handles the secure inclusion of all helper and renderer classes.

### Helpers (Pure logic, no direct HTML output)
- **SubfieldHelper.php**: Responsible for extracting and decoding CISIS subfield values (e.g., `^aValue^bValue`).
- **CalendarHelper.php**: Renders the datepicker button and manages the logic for date selection and ISO format conversion.
- **ConfigHelper.php**: Reads system-level definitions (such as `dr_path.def`) to dynamically enable modern features like Inline Subfields.

### Renderers (HTML Interface Components)
- **TextRenderer.php**: The core component for rendering standard text boxes, password fields, and autoincrement fields.
- **SelectRenderer.php**: Generates dropdown menus (selects) based on Picklists or external authority databases.
- **HtmlAreaRenderer.php**: Handles the integration and instantiation of CKEditor/FCKeditor for rich text content.
- **CheckRenderer.php**: Renders Checkbox and Radio button groups derived from dictionary files.
- **RepeatableRenderer.php**: Manages the interface for simple repeatable text fields.
- **TableRenderer.php**: Renders the complex table grids from the original ABCD logic.
- **GroupRenderer.php**: The heart of the modern interface. It manages compound fields (Type T) and provides a high-performance **Inline Subfields** interface using CSS Grid/Flexbox.
- **TabRenderer.php**: Orchestrates the modern tabbed navigation, managing the structural containers and neutralizing legacy accordion behaviors when tabs are active.

## Implementation Milestone

As of April 19, 2026, the `dibujarhojaentrada.php` file has been successfully refactored into this modular structure. This change ensures:
- **Maintainability:** Easier debugging and addition of new field types.
- **Consistency:** Centralized sanitization and encoding management.
- **Performance:** Optimized rendering of complex MARC records.