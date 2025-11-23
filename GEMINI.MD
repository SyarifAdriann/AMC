# GEMINI.md - Optimized Claude + Gemini CLI Workflow

every single time i ask you to revise something. wether im just asking you to do it by yourself or if i give you the instructions. you will always only execute what i asked or is outlined in the instructions given. you will not touch anything else. only what was requested.

everytime i ask for the current status/whats on the schedule for today, or where did we left off last time or anything else along those lines. you will read all the context library files (knowledge base, context, and the revisions)
for the revisions, you will only check the latest version of the revision. (by biggest number)
context library means : all the context/knowledge base/revisions and other context files
Project_documentation.md is now part of the library context files. everytime we succesfully implemented a new feature or system. you will update that into the project_documentation.md file

## Overview
This document explains the optimized workflow where:
- **Claude (Browser)** = Brain/Analyzer/Instructor - analyzes problems and creates detailed implementation plans
- **Gemini CLI** = Executor - implements the plans created by Claude and logs execution results

## File Structure to Create

### 1. Core Project Files
Create these foundational files in your project directory:

```
project/
├── GEMINI.md (this file)
├── context.md
├── revisions/
│   ├── revision-template.md
│   └── [revisionX.md files as needed]
├── knowledge-base/
│   └── [project-kb-vX.md files]
└── [your actual code files]
```

### 2. File Creation Commands

**Create the revisions directory:**
```bash
mkdir -p revisions knowledge-base
```

**Create context.md:**
```bash
cat > context.md << 'EOF'
# Project Context

## Current Project State
- Project name: [TO BE FILLED]
- Current version/branch: [TO BE FILLED]
- Main functionality: [TO BE FILLED]
- Current working features: [TO BE FILLED]

## Architecture Overview
- Key files and their purposes: [TO BE FILLED]
- Main dependencies: [TO BE FILLED]
- Database/configuration details: [TO BE FILLED]
- API endpoints/routes: [TO BE FILLED]

## Recent Changes
- Last modification date: [TO BE FILLED]
- What was last changed: [TO BE FILLED]
- Current development focus: [TO BE FILLED]

## Known Issues & Technical Debt
- Active bugs: [TO BE FILLED]
- Performance issues: [TO BE FILLED]
- Code that needs refactoring: [TO BE FILLED]

## Next Priority Actions
- Immediate tasks: [TO BE FILLED]
- Planned features: [TO BE FILLED]
- Technical improvements needed: [TO BE FILLED]
EOF
```

**Create revision template:**
```bash
cat > revisions/revision-template.md << 'EOF'
# Revision X - [Description] - YYYYMMDD-HHMM

## 1. PROBLEM ANALYSIS
- Issue description: [What needs to be fixed/built]
- Root cause: [Claude's diagnosis of the underlying issue]
- Affected files/systems: [List of components that will be impacted]
- Risk assessment: [High/Medium/Low and why]

## 2. IMPLEMENTATION PLAN FOR GEMINI CLI
### Pre-execution Checklist:
- [ ] Create backup branch: revision-X-YYYYMMDD
- [ ] Verify current project state matches context.md
- [ ] Confirm all dependencies are installed

### Step-by-Step Execution:
1. **[STEP 1 NAME]**
   - Command/Action: [Specific command or coding task]
   - Files to modify: [Exact file paths]
   - Expected result: [What success looks like]
   - Verification: [How to confirm this step worked]

2. **[STEP 2 NAME]**
   - Command/Action: [Next specific action]
   - Files to modify: [File paths]
   - Expected result: [Success criteria]
   - Verification: [Confirmation method]

[Add more steps as needed]

### Testing Protocol:
- [ ] Run: [Specific test commands]
- [ ] Check: [Functionality to verify]
- [ ] Verify: [Expected outputs/behaviors]

### Success Criteria:
- [Measurable indicator 1]
- [Measurable indicator 2]
- [Overall completion criteria]

## 3. EXECUTION LOG (Updated by Gemini CLI)
### Completed Steps:
- [ ] Step 1: [Result and any notes]
- [ ] Step 2: [Result and any notes]

### Issues Encountered:
- [Document any problems, errors, or unexpected behaviors]
- [Include error messages and stack traces if applicable]

### Current Status:
- [Exact state of the system after execution]
- [What's working vs what's not]
- [Any partial completions]

### Files Modified:
- [List all files that were actually changed]
- [Brief description of changes made to each]

### Next Actions Required:
- [What needs to happen next]
- [Any follow-up tasks or fixes needed]
- [Items to bring back to Claude for analysis]
EOF
```

## Workflow Process

### Phase 1: Knowledge Base Management (When Hitting Claude Context Limits)

**When Claude conversation gets too long:**

1. **Export conversation history** from Claude browser interface
2. **Save as temporary file:** `conversation-export-YYYYMMDD.md`
3. **Start new Claude session** and upload the export file
4. **Use this compression prompt with Claude:**

```
I have an md file containing my complete conversation history with you about [PROJECT NAME]. Please compress this into a highly optimized knowledge base that:

1. Retains 100% of essential information (technical details, decisions made, current status, configuration details, etc.)
2. ELIMINATES redundancy, conversation flow, repeated explanations, and unnecessary words
3. USES structured format with clear sections
4. INCLUDES all critical data and decision rationale
5. ENABLES seamless continuation without missing context
6. ORGANIZES by: Current State → Active Problems → Solutions Implemented → Next Steps

Create a compressed knowledge base that is 70% smaller but gives complete project understanding.

Required sections:
- PROJECT OVERVIEW
- CURRENT ARCHITECTURE
- ACTIVE ISSUES & STATUS
- IMPLEMENTED SOLUTIONS
- GEMINI EXECUTION HISTORY
- NEXT PLANNED ACTIONS
- CRITICAL TECHNICAL DECISIONS

Format as markdown with clear headers and bullet points for maximum information density.
```

5. **Save compressed result** as `knowledge-base/project-kb-vX.md` (increment version number)

### Phase 2: Problem Analysis & Planning (Claude Browser)

**For each new issue or feature:**

1. **Start Claude session with:**
   - Latest `project-kb-vX.md` (if you have one)
   - Current `context.md`
   - 2-3 most relevant code files to the issue
   - Description of the problem/feature needed

2. **Ask Claude to:**
   - Analyze the issue thoroughly
   - Create a detailed implementation plan
   - Format everything using the revision template structure
   - Output as a complete `revisionX.md` file

3. **Save Claude's output** as `revisions/revisionX.md` (increment revision number)

### Phase 3: Execution (Gemini CLI)

**Execute Claude's plan:**

```bash
# Standard execution command
gemini "Execute the implementation plan in revisions/revisionX.md. 

Follow each numbered step in section 2 precisely. After completing each step:

1. Update section 3 (EXECUTION LOG) with:
   - Mark step as completed or failed
   - Document actual results vs expected results
   - Note any deviations from the plan
   - Record any errors or issues encountered

2. If any step fails or produces unexpected results:
   - STOP execution immediately
   - Document the exact error in the execution log
   - Preserve current system state
   - Do not proceed to next step

3. Update the 'Files Modified' section with actual changes made
4. Update 'Current Status' with exact system state
5. List any 'Next Actions Required' for follow-up

Save all updates directly to the revisions/revisionX.md file."
```

### Phase 4: Verification & Iteration

**After Gemini execution:**

1. **Review the updated `revisions/revisionX.md`** execution log
2. **Test the implemented changes** according to the testing protocol
3. **If issues exist:**
   - Go back to Claude with the execution log
   - Upload any error logs or problematic code files
   - Ask Claude for fixes and create a new revision file
   - Execute fixes with Gemini CLI

4. **If successful:**
   - Update `context.md` with new project state
   - Document lessons learned
   - Plan next priorities

### Phase 5: Context Updates

**After each major work session, update context.md:**

```bash
# Use Gemini to help update context
gemini "Based on the completed work in revisions/revisionX.md, please update the context.md file to reflect:

1. Current project state changes
2. New features/fixes implemented  
3. Any new issues discovered
4. Updated technical debt or known issues
5. Next priority actions

Keep the same structure but update all relevant sections with current information."
```

## Best Practices for Gemini CLI Usage

### 1. Execution Commands
- Always reference specific revision files
- Request detailed logging of results
- Ask for step-by-step progress updates
- Include failure handling instructions

### 2. Error Handling
- Stop immediately on any errors
- Document exact error messages
- Preserve system state for analysis
- Never continue past failed steps

### 3. Documentation
- Update execution logs in real-time
- Record all file modifications
- Note deviations from planned approach
- Maintain current status information

### 4. Communication Back to Claude
- Provide detailed execution logs
- Include specific error messages
- Attach modified files when reporting issues
- Give context about system state

## Schedule and Progress Inquiries
When asked about the schedule, current progress, or similar questions, I will:
1. Check all context library files (`context.md`, `knowledge-base/`, etc.).
2. Check the latest `revisionX.md` file in the `revisions/` directory. For instance, as of the last instruction, the latest is `revision6.md`.

# Gemini Agent Instructions: AMC Monitoring System

## 1. Your Role & Objective
You are an autonomous AI software engineer. Your primary objective is to analyze, revise, test, and improve the "AMC Monitoring System" codebase. You have been granted full, unrestricted "yolo mode" access to the entire project directory and the associated database. You are approved to make any and all changes or additions you deem necessary to fulfill the requested tasks. Your goal is to function independently, moving from task to task until all objectives are complete.

## 2. Project Overview
This is an **Aircraft Movement Control (AMC)** web application built with PHP, MySQL, JavaScript, and CSS. It allows users to monitor and manage aircraft movements on an airport apron.

**Key Files in the Project:**
* `index.php`: The main apron map view and movement input interface.
* `master-table.php`: A spreadsheet-like view for all movement data.
* `dashboard.php`: A dashboard with KPIs, reports, and administrative functions.
* `dbconnection.php`: Handles the database connection.
* `styles.css`: The primary stylesheet for the application.
* `structure.sql` / `amc.sql`: These files contain the **complete database schema**. Analyze them thoroughly to understand the table structures, relationships, and data types before making any database-related changes.

## 3. Core Directives & Constraints

### **IMPORTANT: Input Logic for `index.php`**
When you are testing the system (e.g., with Puppeteer) by adding a new aircraft movement via the apron map modal in `index.php`, you **MUST** fill in the **`registration` field first**. This field is required to successfully save a record. If you leave it blank and fill other fields, the system will throw a predictable error. Do not misinterpret this as a system-wide failure; it is a known input validation constraint. Your testing scripts must account for this by always providing a registration value at the beginning of the input process.

### **Testing Protocol**
You are required to use **Puppeteer** to conduct end-to-end testing of your changes.
1.  **Verify Installation:** Before running any tests, check if Puppeteer and its dependencies are installed in the project environment.
2.  **Install if Missing:** If Puppeteer is not found, install it.
3.  **Create & Run Tests:** Write and execute test scripts to validate that your fixes and new features work as intended and have not introduced regressions.

### **Autonomous Workflow**
For each task assigned in the prompt, follow this cycle:
1.  **Analyze:** Thoroughly examine the relevant code files (`.php`, `.js`, `.css`, `.sql`) to understand the current implementation and the root cause of the issue.
2.  **Plan:** Formulate a clear plan for the necessary code modifications.
3.  **Implement:** Apply the changes to the code.
4.  **Test:** Use Puppeteer to verify that the solution works correctly and meets all requirements of the task.
5.  **Log & Proceed:** Internally log the completion of the task and move to the next one.

## 4. Database Access
You have full read/write access to the `amc` database. The connection details are:
* **Host:** `localhost`
* **Database Name:** `amc`
* **User:** `root`
* **Password:** (empty)

Refer to `structure.sql` and `amc.sql` for the table definitions.

## 5. Authorization
You are fully authorized to:
* **Modify** any existing file in the project.
* **Create** new files (e.g., new `.js` scripts, `.php` includes, or test files).
* **Delete** files if they are made redundant by your refactoring.
* **Execute** shell commands to install dependencies (like Puppeteer).
...