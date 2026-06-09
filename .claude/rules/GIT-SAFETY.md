# Git Safety Rules

⚠️ **CRITICAL:** Read before committing anything.

---

## Core Rule

**NEVER auto-commit or auto-push without explicit user permission.**

### What This Means

| Action | Allowed? |
|--------|----------|
| Edit files | ✅ Yes |
| Stage files (`git add`) | ✅ Yes |
| Show diffs (`git diff`) | ✅ Yes |
| Create branches | ✅ Yes |
| Check status | ✅ Yes |
| **COMMIT code** | ❌ **ASK FIRST** |
| **PUSH to remote** | ❌ **ASK FIRST** |
| Force-push | ❌ **WARN THEN ASK** |
| Reset --hard | ❌ **WARN THEN ASK** |
| Rebase -i | ❌ **WARN THEN ASK** |

---

## When Can I Commit?

**Only when user explicitly says one of:**
- "commit these changes"
- "save this work"
- "create a commit"
- "commit with message: ..."
- "commit and push"

**NOT when user says:**
- "save the file" → means write to disk, not git
- "here's the fix" → user providing code, not asking to commit
- "that's good" → user approving code, not committing

---

## Commit Process

When user asks to commit:

### Step 1: Check Status
```bash
git status
git diff
```

### Step 2: Show Changes to User
Display what will be committed.

### Step 3: Ask Confirmation (if unclear)
"Should I stage these files and create a commit with message: '...'?"

### Step 4: Execute
```bash
git add <specific files>
git commit -m "..."
```

### Step 5: Verify
```bash
git status
git log --oneline -3
```

---

## Force-Push & Destructive Ops

### Force-Push (--force, --force-with-lease)

Never use unless:
1. User explicitly says "force push"
2. It's a **feature branch ONLY** (never main/master)
3. You **warn the user first** about consequences

Example warning:
> This will overwrite commits on the remote. Proceed? Type 'yes' to confirm.

### Reset --hard

Never use unless:
1. User explicitly says "reset --hard"
2. You **explain what will be lost**
3. User confirms

### Rebase -i (Interactive)

Never use unless:
1. User explicitly requests it
2. It's a **local-only branch**
3. You **warn about potential conflicts**

---

## Amending Commits

### Local-Only (Not Pushed)
✅ OK to amend: `git commit --amend`

### Already Pushed to Remote
❌ NEVER amend: Create a new commit instead

```bash
# ✅ Correct: New commit for fixes
git commit -m "fix: address review feedback"

# ❌ Wrong: Amending published commit
git commit --amend
```

---

## Summary: Decision Tree

```
User says "commit"?
├─ NO → Don't commit
└─ YES
   ├─ Check status (git diff)
   ├─ Show changes to user
   ├─ Ask "Commit this? Message: ..."
   └─ Execute if approved
        ├─ git add <files>
        ├─ git commit -m "..."
        └─ Verify (git log)

User says "push"?
├─ NO → Don't push
└─ YES
   ├─ Check commits ahead
   ├─ Ask confirmation
   └─ git push origin <branch>

User says "force push"?
├─ NO → Use normal push
└─ YES
   ├─ WARN: "This overwrites commits on remote"
   ├─ Ask confirmation
   └─ git push --force origin <branch>
```

---

## Common Mistakes

### ❌ Auto-commit after every file edit
```bash
# Wrong: Don't do this
git add app/Models/User.php && git commit -m "..."
```

### ❌ Amending published commits
```bash
# Wrong: Commit is on remote
git commit --amend

# Right: Create new commit
git commit -m "fix: address feedback"
```

### ❌ Force-pushing to main
```bash
# Wrong: Never
git push --force origin main

# Right: Feature branch only
git push --force origin feature/my-feature
```

---

See [COMMANDS.md](./COMMANDS.md) for git-related commands.
