# PHP MVC Refactoring Instructions for Codex CLI

## Overview
You are tasked with refactoring an existing procedural PHP project (~8k lines) into a clean MVC (Model-View-Controller) architecture. The project currently has mixed procedural code with functions, frontend, and backend logic all contained within individual page files.

## Critical Requirements
- **PRESERVE ALL FUNCTIONALITY**: Every function, feature, and behavior must work exactly the same after refactoring
- **PRESERVE ALL VISUALS**: Frontend appearance, layouts, and UI elements must remain identical
- **PRESERVE ALL LOGIC**: Backend logic and database operations must function identically
- **IN-PLACE REFACTORING**: Modify the existing codebase directly (backup already exists)

## Three-Phase Process

### Phase 1: Analysis & Planning

#### Step 1: Complete Codebase Analysis
Perform a comprehensive analysis of the entire codebase:

1. **File Mapping**:
   - List all PHP files and their purposes
   - Identify entry points (pages users access directly)
   - Map file dependencies and includes/requires
   - Catalog all functions and their locations

2. **Frontend Analysis**:
   - Identify all HTML structures and layouts
   - Catalog CSS files and inline styles
   - Document JavaScript files and inline scripts
   - Map form elements and their processing

3. **Backend Analysis**:
   - Catalog all functions (name, parameters, return values, purpose)
   - Identify database connections and queries
   - Map session handling and authentication
   - Document file operations and external integrations
   - Identify global variables and constants

4. **Data Flow Analysis**:
   - Map how data flows between pages
   - Identify shared functions and common code
   - Document GET/POST parameter usage
   - Analyze routing patterns (current URL structure)

#### Step 2: Create Analysis Documentation
Generate `CODEBASE_ANALYSIS.md` containing:
- Complete file inventory with descriptions
- Function catalog with signatures and purposes
- Database schema and query patterns
- Frontend component breakdown
- Data flow diagrams (in markdown format)
- Current URL routing analysis
- Identified common patterns and repeated code

#### Step 3: Create Refactoring Plan
Generate `REFACTORING_CHECKLIST.md` containing:
- Recommended MVC directory structure
- Step-by-step refactoring process
- Function-to-class mapping strategy
- View extraction plan
- Controller creation strategy
- Model identification and creation plan
- Routing implementation approach
- Migration order (which files to refactor first)

### Phase 2: Execution

#### MVC Structure Creation
1. **Directory Structure**: Create appropriate MVC directories based on project analysis
2. **Base Classes**: Create core MVC base classes (Controller, Model, View)
3. **Router**: Implement simple routing system to maintain URL functionality
4. **Autoloader**: Create simple autoloader for classes

#### Refactoring Process
1. **Models First**: Extract database-related functions into Model classes
2. **Controllers Next**: Create Controllers for each major page/functionality
3. **Views Last**: Extract HTML/presentation logic into View files
4. **Routing Setup**: Configure routing to match current URL patterns
5. **Testing Each Step**: Verify functionality after each major component migration

#### File-by-File Migration
For each original PHP file:
1. Identify its primary function (page controller, shared functions, etc.)
2. Extract business logic to appropriate Model
3. Extract presentation logic to appropriate View
4. Create or update appropriate Controller
5. Update routing to maintain URL access
6. Test functionality immediately

### Phase 3: Testing & Verification

Create `TESTING_CHECKLIST.md` containing:
- Complete functionality test list
- Visual comparison checklist
- Database operation verification
- Form submission testing
- Session and authentication testing
- File operation testing
- Cross-browser compatibility checks
- Performance comparison notes

## Technical Guidelines

### MVC Implementation Standards
- **Controllers**: Handle HTTP requests, coordinate between Models and Views
- **Models**: Handle data operations, business logic, database interactions
- **Views**: Handle presentation, HTML output, no business logic
- **Router**: Simple routing system that maps URLs to Controllers

### Code Migration Rules
1. **No Logic Changes**: Don't optimize or improve logic during migration
2. **Preserve Variable Names**: Keep existing variable names where possible
3. **Maintain Error Handling**: Preserve all existing error handling
4. **Keep Comments**: Migrate all comments and documentation
5. **Preserve Includes**: Maintain all necessary file includes/requires

### Quality Assurance
- Test each migrated component immediately
- Maintain a working version at each step
- Document any issues encountered during migration
- Preserve all existing security measures

## Success Criteria
- All pages load and function identically to original
- All forms submit and process correctly
- All database operations work as before
- Visual appearance is pixel-perfect match
- No broken links or missing functionality
- Clean, organized MVC structure
- Maintainable and expandable codebase

## Output Files Required
1. `CODEBASE_ANALYSIS.md` - Complete analysis documentation
2. `REFACTORING_CHECKLIST.md` - Detailed step-by-step plan
3. `TESTING_CHECKLIST.md` - Comprehensive testing guide
4. Refactored MVC codebase with clean directory structure