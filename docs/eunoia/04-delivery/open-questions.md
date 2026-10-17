# Open questions

Agents and humans add questions here instead of guessing. Close a question with the answer, date and source.

| ID | Question | Who can answer | Status |
|---|---|---|---|
| Q1 | Is JoFotara "report after issue" or "validate before sharing"? Is there an official sandbox? | ISTD technical guide v1.4, accountant, live test | Open |
| Q2 | **NEEDS ACCOUNTANT.** Full list of reduced GST rates and which goods (perfume oils, alcohol, bottles) fall under which rate. Implemented for now (E1-07): 16 % general, 4 % and 1 % reduced (from the PRD examples), 0 % zero-rated (JoFotara `O`), exempt (`Z`), inactive "special sales tax" placeholder | Accountant | Open |
| Q3 | Retail POS: one JoFotara invoice per sale, or is a summary allowed? Buyer-ID threshold today? | ISTD / accountant | Open |
| Q4 | WhatsApp: Meta Cloud API direct or a BSP? Monthly cost per shop? | Pricing check | Open |
| Q5 | Subscription payments: which gateway (PayTabs, HyperPay, other) or a foreign entity? | Gateway sales calls | Open |
| Q6 | Hosting location requirements under Jordan's Personal Data Protection Law | Lawyer | Open |
| Q7 | Does the upstream Vue till expose an extension point for custom components? | Upstream code / Discussions | Open (E3-03) |
| Q8 | Perfume blends: is alcohol/oil sold by ml or g? How do shops price blends today? | Shop visits | Open |
| Q9 | **NEEDS ACCOUNTANT.** Jordanian retail shelf prices usually include GST. Should POS/products default to tax-included prices (`price_include_override`)? E1-07 seeds tax-excluded taxes like upstream | Accountant / shops | Open |
| Q10 | **NEEDS ACCOUNTANT.** Which special sales tax (ضريبة المبيعات الخاصة) rates/goods apply to perfume shops (alcohol-based perfumes?) — placeholder tax is inactive at 0 % | Accountant | Open |
| Q11 | **NEEDS ACCOUNTANT.** Review the Jordanian chart of accounts (E1-08): Arabic names of the 51 upstream accounts and 13 added accounts (output/input GST 253000/133000, SSC 255000, income tax withheld 254000, perfume stock 110110–110130, perfume/blend revenue 401000/402000, CliQ 101405…). Is a full Jordanian numbering (1/2/3/4/5 classes) required, or is this structure acceptable? | Accountant | Open |
