---
name: feedback-controller-try-catch
description: Every controller method must be wrapped in try-catch — no exceptions
metadata:
  type: feedback
---

Every method in every Controller must be wrapped in a try-catch block — no method is exempt, including simple `create()` and `show()` methods that only return a view.

**Pattern to follow:**
```php
public function methodName()
{
    try {
        // logic here
        return view(...) or redirect(...);
    } catch (\DomainException $e) {
        // for write operations: preserve input
        return back()->with('error', $e->getMessage())->withInput();
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Friendly message.');
    }
}
```

**Rules:**
- Read operations (index, show, edit, create): catch `\Exception`, log with `Log::error()`, return `back()->with('error', ...)`
- Write operations (store, update, destroy): catch `\DomainException` first (for business rule errors), then optionally `\Exception`
- Always `use Illuminate\Support\Facades\Log` in the controller

**Why:** User explicitly requires this as a project-wide standard. All controllers in this project follow this convention.

**How to apply:** When writing or reviewing any Controller method — even a one-liner that just returns a view — wrap it in try-catch. Flag any method missing this as an error, not a suggestion.
