# mod_caseai — Adaptive Case Study for Moodle 4.5+

`mod_caseai` is a formative adaptive case-study activity in which a teacher defines a scenario, case facts, immutable
structural facts,
learning objectives and a deterministic state schema. The student makes professional decisions and AI generates the
next narrative turn, but **AI never owns the state**.

## Required dependency

- Moodle 4.5+
- `local_ai_bridge >= 2026093001`
- AI purpose configured in the bridge: `caseai-simulation`

Every AI call goes exclusively through:

```php
\local_ai_bridge\api::generate('caseai-simulation', $messages, $userid, $options);
```

No provider API key, model selector or direct OpenAI/Gemini/Claude/Ollama call is implemented in this module.

## Deterministic state model

The teacher defines variables as JSON. Example:

```json
{
  "budget": {"type":"number", "initial":100000, "min":0, "max":100000, "mutable":true},
  "risk": {"type":"integer", "initial":1, "min":0, "max":10, "mutable":true},
  "status": {"type":"enum", "initial":"open", "values":["open","contained","closed"], "mutable":true},
  "client_id": {"type":"string", "initial":"ACME-001", "mutable":false}
}
```

The persisted state is separate from narrative text:

```json
{
  "round": 3,
  "variables": {"risk": 4, "status": "contained"},
  "facts": ["The incident started at 09:15"],
  "decisions": ["..."]
}
```

AI receives the current state and a student decision, then returns JSON with `narrative`, proposed `statechanges`,
`nextquestion` and `evidence`. PHP checks every proposed change against the teacher-defined schema and rejects unknown,
immutable, wrong-type or out-of-range changes. Immutable structural facts are copied from the previous state and can
never be replaced by the model, while ordinary case facts are supplied as truths for the current scenario context.

## Failure safety and concurrency

AI is called before a database transaction is opened, so provider/bridge errors do not leave half-open transactions.
After a valid response is parsed, an attempt-level Moodle lock plus an optimistic `stateversion` check protects the
write. Round insertion and state update are committed together. A unique `(attemptid, roundnum)` index and the version
check prevent replay/double-submit from silently creating divergent state.

## Completion and grading

A custom completion rule can require a completed attempt. An attempt completes when a deterministic end criterion is
met or when the configured maximum number of rounds is reached.

No grade is generated automatically. If the teacher configures a maximum grade, the report page allows a user with
`mod/caseai:grade` to review the complete path, optionally request an AI-assisted summary, and enter a grade manually.
The summary cannot modify the state or grade.

## Privacy

The Privacy API implements metadata, context discovery, export, deletion, user-list discovery and deletion for selected
users. Decisions, narratives, evidence and state snapshots are persisted because they form the attempt history. AI
requests are routed through `local_ai_bridge`; institutions should configure retention and providers there according to
their own privacy requirements.

## Rate limit

A persistent per-user/per-activity counter is protected by a Moodle lock. Defaults are 10 simulation requests per 300
seconds and can be changed in site administration.

## End criteria

End criteria are deterministic and ORed. Example:

```json
[
  {"variable":"status", "operator":"==", "value":"closed"},
  {"variable":"risk", "operator":">=", "value":10}
]
```

Supported operators are `==`, `===`, `!=`, `>`, `>=`, `<`, `<=` and `in`.

## Tests

The PHPUnit suite covers the state machine, prohibited changes, malformed AI JSON and core persistence/privacy paths.
The repository CI performs PHP syntax validation and runs EduardoKrausME/moodle-plugin-validate.

## License

GNU GPL v3 or later.
