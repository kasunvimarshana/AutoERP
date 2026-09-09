# TACGL report-expression audit

Audit date: 2026-09-09. Engineering baseline: `5095cdda9b9b9b6589dc582869a1352f51be4156` on `worktree-0.0.8`.

## Scope and method

Read all 109 unencrypted `TACGL/REPORTS/*.frx` tables and their corresponding `.frt` memo files from the already hash-verified uploaded archive. Decode the DBF field descriptors and record boundaries, skip deleted records, resolve four-byte memo pointers through the memo block size and validate file bounds. Decode textual expressions without executing the application or any stored expression. This produced 3,996 non-deleted report records containing extracted `EXPR` and/or `SUPEXPR` text: 3,718 `EXPR` fields and 621 `SUPEXPR` fields.

Inspect Rental-related keyword matches and arithmetic expressions involving multiplication, division and explicit rounding. The extraction traversed every report table; semantic review was targeted to those expressions. This is not a claim that every report object, report execution path, application method or video narration has been audited. Report data preparation and dynamic form variables remain outside this static inspection. Printer configuration text containing `DRIVER` is not business-driver evidence.

Record numbers below are one-based physical DBF record numbers, including any preceding deleted rows. These are static source expressions, not results from executing a financial calculation.

## Findings and decisions

| ID | Exact source | Observed expression / fact | Interpretation and implementation boundary |
|---|---|---|---|
| R01 | `prndebinv.frx`, record 12 | `(jobtxnp.txnsval+jobtxnp.txnvat)/jobtxnp.txnsqty` | A report expression derives a displayed amount per quantity from stored values. This does not prove that the original charge was calculated with that unit rate, resolve narrative inconsistencies such as E02, or establish allowance eligibility. |
| R02 | `prndebinv6.frx`, record 14 | `(jobtxnp.txnsval+jobtxnp.txnnbt+jobtxnp.txnvat)/jobtxnp.txnsqty` | This report expression includes a different set of stored components. Do not collapse these variants into a universal Rental tax-inclusive unit rate. Tax ownership and applicability remain separate. |
| R03 | `prndebiow.frx`, records 64–65 | Record 64 adds stored NBT and VAT per quantity to `txnsrate`; record 65 adds NBT per quantity only. | Preserve the distinction between report presentation and authoritative financial calculation. Stored conditional suppression expressions reference `txncvat`; rendering/selection has not been executed. These expressions do not establish new Rental tax rules. |
| R04 | `prncregrn.frx`, record 27; `prnscfmin.frx`, record 29 | `round((scftxn2.txnqty*scftxn2.txnrate),2)` | Explicit two-decimal rounding exists in purchase/material report expressions. Its scope does not prove Rental calculation rounding or the current Tax/Finance policy. VR-U11 stays unresolved. Do not copy this literal into the Rental engine. |
| R05 | `prndebinv7.frx`, record 22 | Both the displayed “From” date and “To” date reference `frmprndebinv7.txttdate.value`. | The heading alone cannot establish the actual report query interval. Preserve this as a source presentation ambiguity; inspect data preparation before declaring transaction selection wrong. Do not change AutoERP report logic based on this legacy heading. |
| R06 | `prnvehser.frx`, record 8 | Service-due heading includes “Hired Vehicle”; the associated suppression expression is `frmprnvehser.optvehtyp.value = 3`. | Evidence that the report contains a hired-vehicle presentation variant. This does not prove an authoritative Rental source enum, availability blocker or billing deduction. Do not copy numeric option code 3 into the fresh module. |

The inspected expressions did not establish a Rental monthly-proration divisor, included-KM pooling/reset formula, replacement charging rule, garage-mileage charge, driver OT/night-out qualification or deposit disposition policy. That is a bounded negative finding from this inspection, not proof that such logic is absent from the executable, protected backup or video narration.

No source observations resolve the remaining financial gates. Exact rates captured on agreements still do not establish which quantity/unit/period is billable. Preserve known posted amounts and explicitly flag contradictory descriptions; do not reverse-engineer an invented rate to make a narrative agree.

## Source fingerprints

Each referenced report needs both its table and memo file to reproduce the expression lookup. SHA-256 values:

| Source file under `TACGL/REPORTS` | SHA-256 |
|---|---|
| `prndebinv.frx` | `37ddaeba6abf2b6ec2bffec72afa2aa7d3081bfaf74c1840ed550f9ae063185a` |
| `prndebinv.FRT` | `383a6dae52540750b9e8b03ae17a7828a925d95dbe9e20ed6df7a1122d322b12` |
| `prndebinv6.frx` | `3b15185b121c3a1eb154b1f74af6dfbe4e759634d24c27218f5d15f03849e571` |
| `prndebinv6.FRT` | `96df42a8acfa6a09a71b59e3b969d3b21674cbb6ad4a788b591d5640e619e446` |
| `prndebiow.frx` | `80b2f873c80aa5c2b752215005072188d3e92251e585d537168859aeb639cae2` |
| `prndebiow.FRT` | `c35b0bb132cbd2a6b7767e46ad43ae7edfa506c801ffeb6d589ad9dcf1166a2c` |
| `prncregrn.frx` | `ca422bb76dbc520987fc5195795386742a12ecb6190153b47c8367d673b82ed0` |
| `prncregrn.FRT` | `7ccf90961ef51a4dc2811e12858bd62f3d51aff22b8b79d09e4f1116defd83db` |
| `prnscfmin.frx` | `86c8d6c7126a7ea79f18fbe605d1aa30a7ac041f8b9b69871af780da4f940b40` |
| `prnscfmin.FRT` | `efb42701790258e5c8ec3aa6865b38d003bc82a9482305adcf18a09f77c6cd30` |
| `prndebinv7.frx` | `2ab7dc9a14b58279b2a3497a3bc206279278d2fa804a6b636a382cb2c5c6c4e7` |
| `prndebinv7.FRT` | `29ede3d52c86a8afbbe1a97a64c989b0aae3ac8a449247152dec900698113468` |
| `prnvehser.frx` | `536943ed75d6a993662e548148b3794ce1e4229176b712b9896ae9d726eeceb4` |
| `prnvehser.FRT` | `df62a8ecd88a5ca0f21b81bbde4b34c17577c359b90b136c81f1e2d9f8aab61e` |

## Resulting work boundary

No runtime, schema, relationship or module ownership change is justified by these static findings. Keep general financial reporting with its owner modules. Rental must not compensate for possible legacy report presentation defects or import legacy option/account codes as modern rules.

Remaining evidence needed for complete commercial implementation includes the accessible dedicated Rental agreement/Running Chart dataset or application outputs, verified narration/transcript for the four supplied videos, and authoritative resolution of the policy questions in the knowledge base. The password-protected nested backup remains uninspected. This audit neither recovers its password nor substitutes for that missing review.
