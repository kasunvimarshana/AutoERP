from pathlib import Path
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'output' / 'client-presentation'
OUT.mkdir(parents=True, exist_ok=True)
doc = Document()
sec = doc.sections[0]
sec.page_height, sec.page_width = Inches(11.7), Inches(8.3)
sec.top_margin = sec.bottom_margin = Inches(.7)
sec.left_margin = sec.right_margin = Inches(.8)
normal = doc.styles['Normal']
normal.font.name = 'Calibri'
normal.font.size = Pt(11)
normal.paragraph_format.space_after = Pt(7)
normal.paragraph_format.line_spacing = 1.08
for name, size in [('Title', 30), ('Subtitle', 13), ('Heading 1', 21), ('Heading 2', 13)]:
    s = doc.styles[name]
    s.font.name = 'Calibri'
    s.font.size = Pt(size)
    s.font.color.rgb = RGBColor(0, 0, 0)
    s.paragraph_format.space_after = Pt(8)
    if name.startswith('Heading'):
        s.paragraph_format.space_before = Pt(12)
doc.styles['List Bullet'].paragraph_format.space_after = Pt(5)
doc.core_properties.title = 'AutoERP System Features'
doc.core_properties.subject = 'Client presentation of current system capabilities'
doc.core_properties.author = 'AutoERP'
for style in doc.styles:
    for border in style.element.findall('.//' + qn('w:pBdr')):
        border.getparent().remove(border)

def p(text, style=None):
    return doc.add_paragraph(text, style)

def h(text):
    doc.add_heading(text, 2)

def bullets(lines):
    for line in lines:
        p(line, 'List Bullet')

def page(title, intro):
    doc.add_page_break()
    doc.add_heading(title, 1)
    p(intro)

p('AutoERP System Features', 'Title')
p('Client Presentation', 'Subtitle')
p('Current feature overview  |  31 October 2026')
p('AutoERP brings vehicle service, parts sales, purchasing, stock control and financial management into one business system. It also includes vehicle rental operations and employee records, allowing a business to use the modules that match its activities.')
h('What your team can manage')
table = doc.add_table(rows=1, cols=2)
table.columns[0].width = Inches(2.05)
table.columns[1].width = Inches(4.55)
table.rows[0].cells[0].text = 'Business area'
table.rows[0].cells[1].text = 'Main capabilities'
for a, b in [
    ('Workshop operations', 'Service jobs, inspections, parts and labour, employee assignments, invoices and receipts'),
    ('Sales and purchasing', 'Parts sales, returns, purchase orders, goods receipts and supplier settlements'),
    ('Inventory', 'Warehouse stock, availability, batches and serials, movements, counts and valuation'),
    ('Finance', 'Payments, expenses, accounting, tax records and financial statements'),
    ('Rental operations', 'Agreements, vehicle and driver assignments, running charts and billing'),
    ('Management', 'Business dashboard, reports, employee records, branch access and audit logs'),
]:
    row = table.add_row()
    row.cells[0].text, row.cells[1].text = a, b
for i, row in enumerate(table.rows):
    for j, cell in enumerate(row.cells):
        cell.width = Inches(2.05 if j == 0 else 4.55)
        cell.vertical_alignment = 1
        tcPr = cell._tc.get_or_add_tcPr()
        borders = OxmlElement('w:tcBorders')
        for edge in ['top', 'left', 'bottom', 'right']:
            e = OxmlElement('w:' + edge)
            for key, val in [('val','single'), ('sz','4'), ('color','D9D9D9')]:
                e.set(qn('w:' + key), val)
            borders.append(e)
        tcPr.append(borders)
        margins = OxmlElement('w:tcMar')
        for edge in ['top','left','bottom','right']:
            e = OxmlElement('w:' + edge)
            e.set(qn('w:w'), '110')
            e.set(qn('w:type'), 'dxa')
            margins.append(e)
        tcPr.append(margins)
        shade = OxmlElement('w:shd')
        shade.set(qn('w:fill'), '17334A' if i == 0 else ('F2F5F7' if i % 2 else 'FFFFFF'))
        tcPr.append(shade)
        for par in cell.paragraphs:
            par.paragraph_format.space_after = Pt(2)
            for run in par.runs:
                run.font.size = Pt(10.5)
                if i == 0:
                    run.bold = True
                    run.font.color.rgb = RGBColor(255,255,255)
h('Connected daily workflows')
p('A service job can connect customer and vehicle details, stock usage, assigned employees, billing and payment. Purchasing connects incoming goods to supplier invoices and payments. Reports bring these records together for operational and financial review.')
p('Module availability and permitted actions depend on the business configuration and each user\'s access rights.')

page('Customer and Workshop Management', 'Keep customer, vehicle and workshop information connected from job creation through billing.')
h('Customer and vehicle records')
bullets([
    'Maintain customer profiles, contacts, addresses, categories, credit details and supporting documents.',
    'Link customers to their vehicles and maintain vehicle make, model, type, category, ownership and documents.',
    'Create a customer and vehicle within the service job workflow when needed.',
    'Require a customer phone or WhatsApp number and prevent a number from belonging to different customers within the same business.',
    'Review vehicle service history to understand previous jobs and work performed.',
])
h('Service jobs and workshop work')
bullets([
    'Create and track service jobs with complaints, inspection findings, diagnosis and work details.',
    'Add company stock, externally sourced items, customer supplied items, services, labour and combo packages.',
    'Assign employees to work lines and manage supervisor and labour commission policies.',
    'Record job discounts, supporting documents and status history.',
    'Reserve company stock when job lines are saved and issue the reserved stock when the job starts, subject to stock and location checks.',
    'Preview cancellation effects and use controlled cancellation and reversal workflows.',
])
h('Billing and customer communication')
bullets([
    'Preview and create service invoices, collect customer receipts or retain eligible invoices on credit.',
    'Print or download invoices with totals, payment information and outstanding balances.',
    'Prepare a service invoice message and time limited document link for WhatsApp. A staff member completes the send in WhatsApp.',
])

page('Sales Purchasing and Stock Control', 'Connect parts sales and purchasing to stock movements, invoices and payments.')
h('Items and parts sales')
bullets([
    'Manage item categories, brands, variants, codes, units of measure, prices and reorder levels.',
    'Support stock items, consumables, services, labour and combo packages.',
    'Create sales, review sale details and process sales returns with controlled refund actions.',
    'Use units of measure and conversion rules to keep quantities consistent.',
])
h('Purchasing and supplier management')
bullets([
    'Maintain supplier profiles, contacts, addresses, credit details, documents and supplier item mappings.',
    'Create and approve purchase orders, then record goods receipts and related supplier invoices.',
    'Prepare supplier payments and review outstanding supplier obligations.',
    'Process purchase returns, manual supplier returns and debit notes.',
    'Use Fast Purchase for a consolidated purchasing workflow.',
    'Prepare purchase order document links for staff to share through WhatsApp.',
])
h('Inventory and warehouses')
bullets([
    'Manage warehouses and storage locations with stock visibility by item and location.',
    'Review on hand, reserved and available quantities, including low stock and out of stock indicators.',
    'Track batches, serial numbers, stock movements and item stock cards.',
    'Manage stock adjustments, transfers and stock counts through controlled posting steps.',
    'Preview opening stock imports before applying them.',
    'Review inventory valuation and cost adjustments, and record internal consumable usage with reversal support.',
])

page('Billing Payments and Financial Control', 'Follow amounts billed, money collected, supplier payments and business expenses through connected financial records.')
h('Invoices receipts and payments')
bullets([
    'Manage sales, service, rental and purchase invoice records with balances and document history.',
    'Record customer receipts, supplier payments, advances, refunds and manual payments.',
    'Allocate payments to invoices and track unallocated payment balances.',
    'Configure payment methods and manage cheque printing templates and print records.',
    'Review vouchers and linked transaction references; print or download supported documents.',
])
h('Expenses and accounting')
bullets([
    'Record business expenses by expense type and payment method, with controlled posting and reversal.',
    'Manage the chart of accounts, journals and account posting rules.',
    'Review the general ledger, trial balance and account balances.',
    'View profit and loss, balance sheet, cash flow, receivable aging and payable aging reports.',
    'Create budgets, compare actuals and reconcile bank statement lines with recorded activity.',
    'Keep posted financial corrections traceable through linked reversal records.',
])
h('Tax management')
bullets([
    'Configure taxes, rates, tax groups and customer or supplier tax profiles.',
    'Calculate tax for supported transactions and retain transaction tax snapshots.',
    'Review tax reports, liability and reconciliation views.',
])
p('Finance and tax results depend on the configured accounts, posting rules, rates and transaction data. These settings should be reviewed for the client\'s operating requirements during setup.')

page('Rental People and Administration', 'Coordinate rental activity and employee information while controlling access across business locations.')
h('Vehicle rental operations')
bullets([
    'Create and manage rental agreements with effective dated rate versions.',
    'Assign vehicles and drivers, record replacements and track vehicle custody events.',
    'Maintain running charts and calculate rental charges from the applicable agreement and activity.',
    'Configure applicable rental, excess kilometre, air conditioning, driver, overtime and night out rates.',
    'Prepare rental financial documents and continue to invoice or payment settlement workflows.',
    'Review running charts, exceptions, customer invoices, owner vouchers and rental history reports.',
])
h('Employee and workforce records')
bullets([
    'Maintain employee profiles, departments, designations and employment types.',
    'Keep employee contacts, addresses, documents, skills, certifications and licences.',
    'Maintain rates, availability and employment status history.',
    'Review technician work and service job employee commission and incentive reports.',
])
h('Business administration and access')
bullets([
    'Organize business locations using organization units and switch the active branch context.',
    'Manage users, roles, permissions and access to organization units.',
    'Configure business settings, controlled reference values and document numbering.',
    'Review permission controlled audit logs and configuration change history.',
    'Use access controlled documents and time limited shared invoice or purchase order links.',
])

page('Dashboard Reports and Client Demonstration', 'Give managers a business overview and a path to the underlying operational records.')
h('Business overview dashboard')
bullets([
    'Review revenue, receivables, supplier payables, active service jobs and inventory value.',
    'Explore revenue trends, cash movement, service job status and inventory health.',
    'Identify overdue balances, low stock and other action required signals.',
    'Use date filters and drill through to relevant records, subject to access rights.',
])
h('Operational and management reports')
bullets([
    'Business summaries, payment method summaries and customer outstanding balances.',
    'Expense reporting that separates paid expenses, consumable usage and other ledger expenses.',
    'Stock balances, inventory aging, consumable usage and stock cards.',
    'Purchase detail, goods receipt summaries and goods receipt payables.',
    'Vehicle service history, detailed service activity, technician work and employee commissions.',
    'Service Job Sales Summary with stock item and combo performance, component costs, sales, cost and profit.',
    'Rental activity, exceptions, customer invoices, owner vouchers and rental history.',
    'Preview, print and export supported reports to PDF, Excel or CSV. Service sales also offers separate stock and combo performance exports.',
])
h('Suggested demonstration sequence')
for text in [
    'Open the business dashboard and review the current operating position.',
    'Select a customer and vehicle, create a service job and add parts and labour.',
    'Assign employees, show stock availability and progress the job.',
    'Prepare the invoice, record a receipt and show the remaining balance.',
    'Review the service sales report, customer outstanding report and stock movement.',
    'Demonstrate purchasing or rental operations according to the client\'s business.',
]:
    p(text, 'List Number')

footer = sec.footer.paragraphs[0]
footer.alignment = 2
run = footer.add_run('AutoERP  |  ')
run.font.size = Pt(9)
field = OxmlElement('w:fldSimple')
field.set(qn('w:instr'), 'PAGE')
footer._p.append(field)
target = OUT / 'AutoERP_System_Features_Client_Presentation.docx'
doc.save(target)
print(target)
