# Make job card benefits actionable and easy to scan

The service job promotion area now puts the selected active card and its benefits first. Direct benefits and count offers use individual tiles with a clear progress or remaining-use summary and one relevant line action. Card and offer assignment controls sit in an expandable section below the active benefits. The active card uses a status badge instead of a read-only checkbox, and long generated card numbers are shortened in the main view.

The bulk Add available card items action was replaced by a deliberate action on each direct benefit. New jobs add the selected benefit to draft lines; editable saved jobs use the existing version-checked Vehicle Service line API to create the line and Promotion choice atomically. The Promotion module remains responsible for checking availability and calculating the final discount. Existing count offer actions still use the same paid/free flow.

Updated focused UI tests for card issuance, individual free and discounted benefits, remaining uses, and count offer actions. Exercised atomic direct-benefit line creation in the Vehicle Service integration test.
