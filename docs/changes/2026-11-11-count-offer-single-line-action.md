# Use one count offer line action on new and saved jobs

The count offer card now shows one action: Add line for the paid item until a free use is ready, then Add free line for the reward item. New jobs keep the promotion choice on their draft line. Editable saved jobs create the reward line with its promotion choice in one version-checked transaction through the Vehicle Service line API. The endpoint enforces promotion redeem permission when a choice is supplied. Promotion validation and discount calculation remain in the Promotion module.

Added focused UI coverage for switching to the free reward and adapted the count offer integration test to exercise atomic line creation with the earned promotion.
