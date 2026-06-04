# Product Documentation

**Purpose:** Centralized product design, architecture, and implementation plans  
**Owner:** Product & Architecture Team  
**Status:** Living Document

---

## 📁 Structure

```
docs/
├── README.md (this file)
├── DOCUMENTATION_STANDARD.md (how to write docs)
│
└── product/
    ├── README.md (product overview)
    ├── PERMISSION_RBAC/
    │   ├── 00-OVERVIEW.md (executive summary)
    │   ├── 01-REQUIREMENTS.md (what we need)
    │   ├── 02-ARCHITECTURE.md (how it works - with diagrams)
    │   ├── 03-APPROACHES.md (options considered)
    │   └── 04-IMPLEMENTATION_PLAN_B.md (detailed steps)
    │
    └── [future features]/
        ├── 00-OVERVIEW.md
        ├── 01-REQUIREMENTS.md
        ├── 02-ARCHITECTURE.md
        └── 03-IMPLEMENTATION_PLAN.md
```

---

## 🎯 Quick Links

- **Permission/RBAC Feature** → [docs/product/PERMISSION_RBAC/00-OVERVIEW.md](./product/PERMISSION_RBAC/00-OVERVIEW.md)

---

## 📋 Guidelines

- Every major feature gets its own folder under `docs/product/`
- Each folder follows the **4-part structure:** Overview → Requirements → Architecture → Plan
- Use **Mermaid diagrams** for visual clarity
- Update docs BEFORE implementing (design-first approach)
- Link to code files when referencing specific implementations

**→ Read [DOCUMENTATION_STANDARD.md](./DOCUMENTATION_STANDARD.md) for full guidelines**

---

## 🔄 Workflow

```
1. Feature request arrives
    ↓
2. Create feature folder + 00-OVERVIEW.md
    ↓
3. Write 01-REQUIREMENTS.md (what, why, scope)
    ↓
4. Write 02-ARCHITECTURE.md (how, diagrams)
    ↓
5. Review & discuss with team
    ↓
6. Write 03-[OPTIONS].md if multiple approaches
    ↓
7. Choose approach, write 04-IMPLEMENTATION_PLAN.md
    ↓
8. Get approval, then CODE
    ↓
9. Link PRs to docs
```

---

## 📝 Document Ownership

| Document | Owner | Review By |
|---|---|---|
| OVERVIEW | Product | Tech Lead |
| REQUIREMENTS | Product | Architect |
| ARCHITECTURE | Architect | Tech Lead + Product |
| IMPLEMENTATION | Tech Lead | QA |

---

## 🗂️ How to Add New Feature Docs

```bash
# 1. Create folder
mkdir docs/product/FEATURE_NAME

# 2. Copy template files (see DOCUMENTATION_STANDARD.md)
# 3. Fill in each file
# 4. Create PR with docs-only changes
# 5. Get review
# 6. Merge
# 7. Code phase starts
```

---

## 📌 Version History

| Version | Date | Changes | Author |
|---|---|---|---|
| 1.0 | 2026-06-04 | Initial structure, Permission/RBAC docs | Architecture |

