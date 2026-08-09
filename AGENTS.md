# AGENTS.md — Vortex Mod List Exporter

> **Operating Manual for AI Agents**
> Read this file before touching any code. It tells you where to look, what the rules are, and how to avoid common mistakes.

---

## 1. Project Manifest — Start Here!

The Project Manifest is the **canonical source of truth** for this codebase. Always consult it before reading source files.

**Location:** `docs/agents/project-manifest/`

| Document | Description |
|---|---|
| [README.md](docs/agents/project-manifest/README.md) | Project overview, purpose, and manifest table of contents |
| [tech-stack.md](docs/agents/project-manifest/tech-stack.md) | PHP runtime, dependencies, Composer scripts, architectural patterns |
| [file-tree.md](docs/agents/project-manifest/file-tree.md) | Annotated directory structure of the entire project |
| [api-surface.md](docs/agents/project-manifest/api-surface.md) | Public constructors, properties, and method signatures for every class in `src/` |
| [data-flows.md](docs/agents/project-manifest/data-flows.md) | Export pipeline and document generation pipeline step by step |
| [constraints.md](docs/agents/project-manifest/constraints.md) | Conventions, configuration rules, naming patterns, and non-obvious gotchas |

### Quick Start Workflow

Read in this order before making any changes:

1. **[README.md](docs/agents/project-manifest/README.md)** — Understand what the project does and why.
2. **[tech-stack.md](docs/agents/project-manifest/tech-stack.md)** — Internalize the runtime, dependencies, and architectural patterns.
3. **[constraints.md](docs/agents/project-manifest/constraints.md)** — Read every rule. Many behaviors are non-obvious.
4. **[file-tree.md](docs/agents/project-manifest/file-tree.md)** — Know where everything lives before opening files.
5. **[api-surface.md](docs/agents/project-manifest/api-surface.md)** — Reference method signatures before editing or calling code.
6. **[data-flows.md](docs/agents/project-manifest/data-flows.md)** — Trace the pipeline when debugging or extending behaviour.

---

## 2. Manifest Maintenance Rules

When you make a code change, update the corresponding manifest documents **in the same pass**.

| Change Made | Documents to Update |
|---|---|
| New class added to `src/` | `api-surface.md`, `file-tree.md` |
| Existing method signature changed | `api-surface.md` |
| New Composer script registered | `tech-stack.md`, `data-flows.md` |
| New dependency added or removed | `tech-stack.md` |
| Directory restructured or file moved | `file-tree.md` |
| New game config field or option key added | `api-surface.md` (constants), `constraints.md` |
| Export pipeline logic changed | `data-flows.md` |
| New output file format introduced | `constraints.md`, `data-flows.md`, `file-tree.md` |
| New tag inheritance or mod-naming rule | `constraints.md` |
| New configuration constant added | `constraints.md` |
| New lint rule added to `src/ModLint/Rules/` | `api-surface.md`, register in `ModLinter::RULE_REGISTRY` |

---

## 3. Efficiency Rules — Search Smart

The manifest exists to prevent unnecessary filesystem scanning. Apply these rules strictly:

- **Finding a file's location?** Check `file-tree.md` **first**.
- **Looking up a method or class?** Check `api-surface.md` **first**.
- **Understanding a pattern or convention?** Check `tech-stack.md` or `constraints.md` **first**.
- **Tracing execution flow?** Check `data-flows.md` **first**.
- **Only then** open source files in `src/` to verify implementation details.

Do not `grep` the codebase for information that the manifest already provides. The manifest is kept in sync with the code — trust it.

---

## 4. Failure Protocol & Decision Matrix

| Scenario | Action | Priority |
|---|---|---|
| Ambiguous requirement | Use the most restrictive interpretation | MUST |
| Manifest/code conflict | Trust the manifest; flag the code as needing a fix | MUST |
| Missing documentation | Flag the gap explicitly; do not invent facts | MUST |
| `config.php` is absent | Do not create it automatically; instruct the user to copy `config.dist.php` | MUST |
| `VORTEX_APPDATA_FOLDER` path is invalid | Report the constraint from `constraints.md`; do not work around it | MUST |
| Backup file `manual.json` is missing | Report the Vortex workflow from `constraints.md`; do not fabricate test data | MUST |
| Adding a web layer or HTTP endpoint | Refuse — the project is CLI-only by design (see `constraints.md`) | MUST NOT |
| Removing `declare(strict_types=1)` from any PHP file | Refuse — strict types are mandatory in all files | MUST NOT |
| Adding a class outside the `Mistralys\VortexModExporter` namespace | Reject; all code belongs in this namespace | MUST NOT |
| Untested code path | Proceed with caution; note the gap since the project has no test suite | SHOULD |
| Game config file placed in a subdirectory of `games/` | Flag as invalid; the auto-discovery ignores subdirectories | MUST flag |

---

## 5. Project Stats

| Property | Value |
|---|---|
| Language | PHP 8.4+ |
| Execution model | CLI only (Composer scripts) |
| Architecture | Static singleton registry + typed collection base classes + data-object pattern |
| Package manager | Composer |
| Autoloading | Classmap (`src/` — no PSR-4) |
| Namespace | `Mistralys\VortexModExporter` |
| Test framework | None |
| Build tool | Composer scripts (`composer build`, `composer normalize-game-configs`, `composer export-modlist`, `composer generate-docs`) |
| Web layer | None — must never be added |
