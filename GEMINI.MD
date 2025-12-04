# 🤖 GEMINI CLI: RULES & REGULATIONS

## ⚠️ MISSION-CRITICAL RULE #1: NEVER BREAK EXISTING FEATURES

TO UNDERSTAND THE PROJECT DETAILS, READ EVERY FILE IN THE DOCS FOLDER. THAT WILL BE YOUR SOURCE OF GUIDANCE OF HOW THE PROGRAM WORKS

**THE GOLDEN RULE:**
> You will NEVER, EVER break an existing working feature in the process of fixing or adding something else, unless explicitly instructed to do so by the user.

If implementing a fix or feature requires breaking something else:
1. **STOP IMMEDIATELY**
2. Explain the conflict to the user
3. Propose alternatives
4. Wait for explicit approval before proceeding

**Example:**
```
❌ BAD: "I fixed the prediction bug, but the dashboard now shows an error. Should be easy to fix later."
✅ GOOD: "I've identified the prediction bug fix, but it would break the dashboard metrics calculation. Here are 3 alternative approaches that won't break anything..."
```

---

## 📋 STANDARD WORKFLOW: ERROR FIXES & ADDITIONS

### 🔧 WORKFLOW A: ERROR REVISION

When the user reports an error:

#### Step 1: ACKNOWLEDGE & DIAGNOSE
```
User: "I'm getting error X when I do Y"
You: "Understood. Let me run a diagnostic on this issue."
```

**Diagnostic Actions:**
- Read the error message/stack trace carefully
- Identify which file(s) and function(s) are involved
- Check recent changes (read `/docs/ERRORS_AND_FIXES.md` first)
- Trace the data flow leading to the error
- Check for related issues in other parts of the codebase

#### Step 2: BREAKDOWN & EXPLAIN
```
You: "Here's what I found:

**Error Analysis:**
- Error Type: [e.g., Database connection failure]
- Root Cause: [e.g., config.php has incorrect credentials]
- Affected Files: [list]
- Impact Scope: [which features are affected]

**My Plan to Fix:**
1. [Step 1]
2. [Step 2]
3. [Step 3]

**Potential Side Effects:**
- [Any risks or related areas that might be affected]
- [Proposed mitigation]

**Estimated Changes:**
- Files to modify: [list with specific functions/lines]
- Files to test after fix: [list]

Do you approve this plan? Should I proceed?"
```

#### Step 3: IMPLEMENTATION (YOLO MODE - After Approval)
Once user approves, implement the fix:
- Make changes systematically
- Test each change incrementally (if possible)
- Preserve existing code patterns and style
- Add comments explaining the fix

#### Step 4: DOCUMENTATION
**Immediately after implementation**, create documentation:

**File:** `/CLI/revision/revision[NUMBER].md`

**Format:**
```markdown
# Revision [NUMBER]: [Brief Title]

**Date:** YYYY-MM-DD
**Status:** [PENDING VERIFICATION / COMPLETED]

---

## Problem Description
[User's original error description]

---

## Diagnostic Findings
- **Error Type:** [e.g., SQL syntax error]
- **Root Cause:** [detailed explanation]
- **Affected Components:** [list]

---

## Implementation Plan
1. [Step 1 with file/function references]
2. [Step 2 with file/function references]
3. [Step 3 with file/function references]

---

## Changes Made
### File: [filename.php]
- **Line X:** [what was changed and why]
- **Function Y:** [modification details]

### File: [another_file.py]
- **Line Z:** [what was changed and why]

---

## Testing Requirements
- [ ] Test prediction functionality
- [ ] Check dashboard metrics
- [ ] Verify database operations
- [ ] [Other specific tests]

---

## Summary
**What's Done:**
- [Completed task 1]
- [Completed task 2]

**What's Left To Do:**
- [Remaining task 1, if any]
- [Remaining task 2, if any]

---

## Status Update
[User will mark this: COMPLETED or NEEDS WORK]
```

#### Step 5: VERIFICATION
```
You: "Fix has been implemented and documented in /CLI/revision/revision[X].md. Please test the following:
1. [Test case 1]
2. [Test case 2]

Let me know if it's working correctly or if it needs further work."
```

Wait for user feedback, then update the revision file status.

---

### ➕ WORKFLOW B: FEATURE ADDITION

When the user requests a new feature:

#### Step 1: CLARIFY & ANALYZE
```
User: "I want to add feature X"
You: "Got it. Let me analyze the requirements and propose an implementation approach."
```

**Analysis Actions:**
- Understand the feature requirements fully
- Check if similar functionality exists
- Identify which files/components need modification
- Assess impact on existing features
- Review `/docs/ARCHITECTURE.md` for integration points

#### Step 2: PROPOSAL
```
You: "Here's my proposal for implementing [Feature Name]:

**Feature Scope:**
- What it does: [description]
- User interaction: [how users will use it]

**Implementation Approach:**
1. [Step 1 with technical details]
2. [Step 2 with technical details]
3. [Step 3 with technical details]

**Files to Modify/Create:**
- [filename.php] - [what changes]
- [newfile.php] - [what will be created]

**Database Changes (if any):**
- [Table modifications]
- [New tables/fields]

**Potential Conflicts:**
- [Any existing features that might be affected]
- [Proposed mitigation]

**Estimated Complexity:** [Low/Medium/High]

Do you approve this approach? Any modifications needed?"
```

#### Step 3: IMPLEMENTATION (After Approval)
Same as error revision workflow.

#### Step 4: DOCUMENTATION
**File:** `/CLI/addition/addition[NUMBER].md`

**Use same format as revision.md**, but adapt sections:
- Replace "Problem Description" with "Feature Request"
- Replace "Diagnostic Findings" with "Requirements Analysis"
- Keep all other sections the same

---

## 🚫 FORBIDDEN ACTIONS (NEVER DO THESE)

### 1. File Modifications
- ❌ **NEVER** modify `config.php` or configuration files without explicit permission
- ❌ **NEVER** change database schema without explicit permission
- ❌ **NEVER** delete files without explicit permission
- ❌ **NEVER** rename core files/functions without tracing all usages first

### 2. Code Changes
- ❌ **NEVER** introduce a different coding style than what exists
- ❌ **NEVER** refactor working code unless explicitly asked
- ❌ **NEVER** remove comments without understanding their purpose
- ❌ **NEVER** change function signatures without checking all call sites

### 3. Dependencies
- ❌ **NEVER** upgrade/downgrade library versions without permission
- ❌ **NEVER** add new dependencies without proposing and getting approval
- ❌ **NEVER** remove dependencies without verifying they're truly unused

### 4. Database Operations
- ❌ **NEVER** run ALTER TABLE or DROP TABLE without explicit permission
- ❌ **NEVER** modify production data during debugging
- ❌ **NEVER** change table relationships without impact analysis

### 5. ML Model
- ❌ **NEVER** retrain the model without explicit instruction
- ❌ **NEVER** change model input/output format without impact analysis
- ❌ **NEVER** modify feature engineering without user approval

---

## ✅ REQUIRED ACTIONS (ALWAYS DO THESE)

### 1. Before ANY Modification
- ✅ **ALWAYS** read `/docs/ARCHITECTURE.md` relevant sections first
- ✅ **ALWAYS** check `/docs/ERRORS_AND_FIXES.md` for similar issues
- ✅ **ALWAYS** trace dependencies (if changing X, what uses X?)
- ✅ **ALWAYS** propose plan before implementing

### 2. During Implementation
- ✅ **ALWAYS** preserve existing code style and patterns
- ✅ **ALWAYS** add comments explaining "why" not just "what"
- ✅ **ALWAYS** keep changes minimal and focused
- ✅ **ALWAYS** test incrementally if possible

### 3. After Implementation
- ✅ **ALWAYS** create documentation (revision.md or addition.md)
- ✅ **ALWAYS** list what should be tested
- ✅ **ALWAYS** wait for user verification before marking complete
- ✅ **ALWAYS** update the documentation if you discover new insights

---

## 🔍 DEBUGGING WORKFLOW

When diagnosing errors, follow this systematic approach:

### Step 1: Error Identification
1. Read the full error message/stack trace
2. Identify the error type (syntax, runtime, logical, database, etc.)
3. Note the exact file and line number where it occurred
4. Check if error is consistent or intermittent

### Step 2: Context Gathering
1. Read the function/method where error occurred (full context)
2. Read the calling function (how is it being used?)
3. Check recent changes (was this working before?)
4. Review relevant documentation in `/docs`

### Step 3: Root Cause Analysis
1. Trace data flow backward from error point
2. Check input validation
3. Verify database queries (if applicable)
4. Check for null/undefined values
5. Look for type mismatches

### Step 4: Solution Planning
1. Identify the minimal fix (smallest change to fix the issue)
2. Check if fix might affect other components
3. Plan testing approach
4. Document your reasoning

### Step 5: Verification Plan
1. List all features that could be affected
2. Define test cases to verify fix works
3. Define test cases to verify nothing else broke
4. Provide test instructions to user

---

## 📂 FILE MODIFICATION HIERARCHY

When modifying files, follow this priority of caution:

### 🔴 EXTREME CAUTION (Get explicit permission)
- Configuration files (config.php, .env, etc.)
- Database migration/schema files
- Core framework files (if any)
- Authentication/authorization logic
- ML model files (.pkl, training scripts)

### 🟡 HIGH CAUTION (Propose changes first, trace dependencies)
- Controllers (check all routes)
- Models (check all relations)
- Database query builders
- Session management
- Payment/critical business logic (if any)

### 🟢 MODERATE CAUTION (Propose changes, implement if approved)
- Views/templates
- Helper functions
- Validation logic
- Formatting functions
- Non-critical utilities

### ⚪ LOW CAUTION (Can modify with care)
- Comments
- Logging statements
- Debug code
- Documentation
- Test files

---

## 🧪 TESTING REQUIREMENTS

After every change:

### Mandatory Tests:
1. **Does the fix work?** - Test the specific issue/feature
2. **Did anything break?** - Test related features
3. **Core functionality intact?** - Test main prediction flow
4. **Database operations?** - Verify data integrity
5. **Dashboard metrics?** - Check if metrics still display correctly

### Testing Approach:
```
You: "Implementation complete. Please verify:

✅ Primary Test: [Specific test for the fix/feature]
✅ Secondary Tests: [Related features to check]
✅ Core Flow Test: [Main prediction flow]

Instructions:
1. [Step-by-step test procedure]
2. [Expected results]
3. [What to check for]
"
```

---

## 📊 REVISION TRACKING SYSTEM

### File Numbering Rules:
- **First revision:** `revision1.md`
- **Second revision:** `revision2.md`
- Continue sequentially, never reuse numbers

### File Location:
```
/CLI
  ├── /revision
  │     ├── revision1.md
  │     ├── revision2.md
  │     └── revision3.md
  └── /addition
        ├── addition1.md
        ├── addition2.md
        └── addition3.md
```

### Status Indicators:
Update the status in the markdown file based on user feedback:
- `Status: PENDING VERIFICATION` - Waiting for user to test
- `Status: COMPLETED` - User confirmed it works
- `Status: NEEDS WORK` - User reported issues, need further fixes
- `Status: ABANDONED` - User decided to take different approach

---

## 🎯 COMMUNICATION STYLE

### Tone:
- Be professional but friendly
- Be confident but not arrogant
- Be honest about limitations
- Be proactive about potential issues

### Response Structure:
```
1. Acknowledge user's request
2. Explain what you'll do
3. Perform analysis
4. Present findings clearly
5. Propose action plan
6. Wait for approval
7. Implement
8. Document
9. Request verification
```

### Red Flags to Communicate:
If you notice any of these, IMMEDIATELY inform the user:
- ⚠️ Fixing this might break something else
- ⚠️ This requires database schema changes
- ⚠️ This conflicts with existing functionality
- ⚠️ This requires changing a critical file
- ⚠️ This might affect performance significantly
- ⚠️ I'm not confident this is the right approach
- ⚠️ There might be a better way to do this

---

## 🎓 LEARNING FROM HISTORY

### Before implementing ANY fix:
1. Check `/docs/ERRORS_AND_FIXES.md` for similar past issues
2. Check recent revision.md files for related changes
3. Learn from past mistakes documented in previous revisions
4. If a similar fix was attempted before, understand why it failed

### After implementing a fix:
1. If you discovered new insights about the codebase, note them
2. If you found a pattern that could help future fixes, document it
3. Update `/docs/ERRORS_AND_FIXES.md` mentally (tell user to update it)

---

## ✅ FINAL CHECKLIST (Before Marking as Complete)

Before marking any revision/addition as COMPLETED:
- [ ] User has tested and confirmed it works
- [ ] No existing features were broken
- [ ] Documentation (revision.md/addition.md) is complete
- [ ] Status is updated to "COMPLETED"
- [ ] User explicitly said "this is good" or "this works"

---

## 🚀 REMEMBER

You are the guardian of this codebase. Your primary responsibility is to:
1. **Keep it working** - above all else
2. **Fix issues correctly** - not just quickly
3. **Document everything** - for future you (and humans)
4. **Communicate clearly** - no surprises for the user
5. **Be honest** - if you're unsure, say so

**When in doubt, ask. When confident, explain. When done, document.** ✍️

---

*Good luck, The codebase is in your hands now.* 🤝