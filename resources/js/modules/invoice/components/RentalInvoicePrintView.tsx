import { useState, useMemo } from 'react';
import type { Invoice } from '../invoiceTypes';
import { formatDate } from '@/shared/utils/formatDate';
import { Button, LinkButton } from '@/shared/components/Button';

interface RentalInvoicePrintViewProps {
    invoice: Invoice;
}

interface CalculatedLineItem {
    id: string | number;
    code: string;
    description: string;
    subDescription: string;
    quantity: number;
    unitPrice: number;
    netAmount: number;
    taxAmount: number;
    lineTotal: number;
}

export default function RentalInvoicePrintView({ invoice }: RentalInvoicePrintViewProps) {
    const partyName = invoice.party?.name || invoice.party?.legal_name || 'Valued Rental Customer';
    const partyAddress = String(invoice.party?.address || 'Colombo, Sri Lanka');
    const partyPhone = String(invoice.party?.phone || '+94 77 000 0000');
    const partyEmail = String(invoice.party?.email || 'accounts@client.com');
    const partyTaxNumber = String(invoice.party?.tax_registration_number || 'VAT-10928374-7000');

    // Customer & Driver Details
    const [driverName, setDriverName] = useState('Kamal Perera');
    const [driverNic, setDriverNic] = useState('198512304589');
    const [driverLicense, setDriverLicense] = useState('B-3829104-WP');
    const [licenseExpiry, setLicenseExpiry] = useState('15-Aug-2029');

    // Vehicle & Dates
    const [vehicleModel, setVehicleModel] = useState('Toyota Prius 1.8 Hybrid');
    const [vehiclePlate, setVehiclePlate] = useState('CAB-4528');
    const [rentalMode, setRentalMode] = useState('Chauffeur Driven');
    const [startDate, setStartDate] = useState(invoice.invoice_date ? `${invoice.invoice_date} 09:00` : '2026-10-01 09:00');
    const [returnDate, setReturnDate] = useState(invoice.due_date ? `${invoice.due_date} 09:00` : '2026-10-11 09:00');
    const [rentalDays, setRentalDays] = useState(10);

    // Mileage & Odometer
    const [startOdo, setStartOdo] = useState(45200);
    const [returnOdo, setReturnOdo] = useState(47050);
    const [includedKmPerDay, setIncludedKmPerDay] = useState(150);
    const [excessKmRate, setExcessKmRate] = useState(95);

    // Rates & Itemized Charges
    const [baseDailyRate, setBaseDailyRate] = useState(12500);
    const [driverDailyRate, setDriverDailyRate] = useState(3500);
    const [driverDays, setDriverDays] = useState(10);
    const [fuelLitres, setFuelLitres] = useState(25);
    const [fuelPricePerLitre, setFuelPricePerLitre] = useState(370);
    const [cdwDailyRate, setCdwDailyRate] = useState(1800);
    const [cdwDays, setCdwDays] = useState(10);
    const [addonDailyRate, setAddonDailyRate] = useState(800);
    const [addonDays, setAddonDays] = useState(10);

    // Taxes & Deposit
    const [vatPercent, setVatPercent] = useState(18.0);
    const hasExistingDeposit = Number(invoice.credit_total || 0) > 0;
    const [depositCredit, setDepositCredit] = useState(
        hasExistingDeposit
            ? String(invoice.credit_total)
            : (invoice.lines && invoice.lines.length > 0 ? '0.00' : '50,000.00'),
    );

    const [isEditing, setIsEditing] = useState(false);
    const hasInvoiceLines = Boolean(invoice.lines && invoice.lines.length > 0);
    const [useCustomCalculator, setUseCustomCalculator] = useState(!hasInvoiceLines);

    // Dynamic Odometer Calculations
    const totalDrivenKm = Math.max(0, returnOdo - startOdo);
    const totalIncludedKm = rentalDays * includedKmPerDay;
    const billableExcessKm = Math.max(0, totalDrivenKm - totalIncludedKm);

    const currencySymbol = invoice.currency?.symbol || invoice.currency?.code || 'LKR';

    const formatCurrency = (val: number | string | undefined) => {
        const num = Number(val ?? 0);
        return Number.isNaN(num) ? '0.00' : num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    // Derived dynamic line items for the itemized calculation
    const calculatedLineItems: CalculatedLineItem[] = useMemo(() => {
        const vatFactor = vatPercent / 100;

        // 1. Base Vehicle Hire Rate
        const baseNet = rentalDays * baseDailyRate;
        const baseTax = baseNet * vatFactor;

        // 2. Excess Mileage
        const mileageNet = billableExcessKm * excessKmRate;
        const mileageTax = mileageNet * vatFactor;

        // 3. Driver Charges
        const driverNet = driverDays * driverDailyRate;
        const driverTax = driverNet * vatFactor;

        // 4. Refueling Deficit
        const fuelNet = fuelLitres * fuelPricePerLitre;
        const fuelTax = fuelNet * vatFactor;

        // 5. CDW Protection
        const cdwNet = cdwDays * cdwDailyRate;
        const cdwTax = cdwNet * vatFactor;

        // 6. Add-ons
        const addonNet = addonDays * addonDailyRate;
        const addonTax = addonNet * vatFactor;

        return [
            {
                id: 'base-hire',
                code: '01',
                description: 'Base Vehicle Hire Rate',
                subDescription: `${vehicleModel} (${rentalDays} Days @ ${formatCurrency(baseDailyRate)}/day, ${includedKmPerDay} km/day free)`,
                quantity: rentalDays,
                unitPrice: baseDailyRate,
                netAmount: baseNet,
                taxAmount: baseTax,
                lineTotal: baseNet + baseTax,
            },
            {
                id: 'excess-mileage',
                code: '02',
                description: 'Excess Mileage Charge',
                subDescription: `${billableExcessKm} km over allowance @ ${currencySymbol} ${formatCurrency(excessKmRate)}/km`,
                quantity: billableExcessKm,
                unitPrice: excessKmRate,
                netAmount: mileageNet,
                taxAmount: mileageTax,
                lineTotal: mileageNet + mileageTax,
            },
            {
                id: 'driver-allowance',
                code: '03',
                description: 'Chauffeur / Driver Daily Allowance',
                subDescription: `${driverDays} Days driver allowance @ ${currencySymbol} ${formatCurrency(driverDailyRate)}/day`,
                quantity: driverDays,
                unitPrice: driverDailyRate,
                netAmount: driverNet,
                taxAmount: driverTax,
                lineTotal: driverNet + driverTax,
            },
            {
                id: 'fuel-deficit',
                code: '04',
                description: 'Refueling Service & Fuel Deficit',
                subDescription: `${fuelLitres} Litres petrol deficit @ ${currencySymbol} ${formatCurrency(fuelPricePerLitre)}/L`,
                quantity: fuelLitres,
                unitPrice: fuelPricePerLitre,
                netAmount: fuelNet,
                taxAmount: fuelTax,
                lineTotal: fuelNet + fuelTax,
            },
            {
                id: 'cdw-premium',
                code: '05',
                description: 'Collision Damage Waiver (CDW Premium)',
                subDescription: `Comprehensive damage protection (${cdwDays} Days @ ${currencySymbol} ${formatCurrency(cdwDailyRate)}/day)`,
                quantity: cdwDays,
                unitPrice: cdwDailyRate,
                netAmount: cdwNet,
                taxAmount: cdwTax,
                lineTotal: cdwNet + cdwTax,
            },
            {
                id: 'addon-wifi-gps',
                code: '06',
                description: 'Add-on: Portable 4G WiFi & GPS Hotspot',
                subDescription: `High-speed connectivity (${addonDays} Days @ ${currencySymbol} ${formatCurrency(addonDailyRate)}/day)`,
                quantity: addonDays,
                unitPrice: addonDailyRate,
                netAmount: addonNet,
                taxAmount: addonTax,
                lineTotal: addonNet + addonTax,
            },
        ];
    }, [
        rentalDays,
        baseDailyRate,
        billableExcessKm,
        excessKmRate,
        driverDays,
        driverDailyRate,
        fuelLitres,
        fuelPricePerLitre,
        cdwDays,
        cdwDailyRate,
        addonDays,
        addonDailyRate,
        vatPercent,
        vehicleModel,
        includedKmPerDay,
        currencySymbol,
    ]);

    // Financial Totals calculation
    const depositNumber = Math.max(0, Number(depositCredit.replace(/[^0-9.-]+/g, '')) || 0);

    const { subtotal, taxTotal, grossTotal } = useMemo(() => {
        if (!useCustomCalculator && hasInvoiceLines && invoice.lines) {
            let sub = 0;
            let tax = 0;
            let gross = 0;
            for (const line of invoice.lines) {
                const q = Number(line.quantity) || 1;
                const p = Number(line.unit_price) || 0;
                const lineNet = q * p > 0 ? q * p : Number(line.line_total || 0) - Number(line.tax_amount || 0);
                const lineTax = Number(line.tax_amount || 0);
                const lineTot = Number(line.line_total || (lineNet + lineTax));
                sub += lineNet;
                tax += lineTax;
                gross += lineTot;
            }
            return {
                subtotal: invoice.subtotal ? Number(invoice.subtotal) : sub,
                taxTotal: invoice.tax_total ? Number(invoice.tax_total) : tax,
                grossTotal: invoice.grand_total ? Number(invoice.grand_total) : gross,
            };
        }

        const sub = calculatedLineItems.reduce((acc, item) => acc + item.netAmount, 0);
        const tax = calculatedLineItems.reduce((acc, item) => acc + item.taxAmount, 0);
        const gross = sub + tax;
        return { subtotal: sub, taxTotal: tax, grossTotal: gross };
    }, [useCustomCalculator, hasInvoiceLines, invoice.lines, invoice.subtotal, invoice.tax_total, invoice.grand_total, calculatedLineItems]);

    const netBalance = Math.max(0, grossTotal - depositNumber);
    const refundDue = Math.max(0, depositNumber - grossTotal);

    return (
        <div className="space-y-4">
            {/* Top Toolbar (Hidden on print) */}
            <div className="print:hidden flex flex-wrap justify-between items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl p-4">
                <div className="flex items-center gap-2">
                    <span className="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span className="text-sm font-semibold text-slate-800">Vehicle Rental Detailed Tax Invoice</span>
                    {hasInvoiceLines && (
                        <span className="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-medium ml-2">
                            {useCustomCalculator ? 'Interactive Calculator Mode' : 'Recorded Invoice Lines'}
                        </span>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    {hasInvoiceLines && (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => setUseCustomCalculator(!useCustomCalculator)}
                        >
                            {useCustomCalculator ? 'Switch to Invoice Lines' : 'Switch to Custom Calculator'}
                        </Button>
                    )}
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => setIsEditing(!isEditing)}
                    >
                        {isEditing ? '✓ Done Editing' : '✎ Edit Specifications'}
                    </Button>
                    <Button
                        type="button"
                        onClick={() => window.print()}
                    >
                        Print / Save PDF
                    </Button>
                    {invoice.direction === 'outbound' && Number(invoice.balance_due ?? 0) > 0 && (
                        <LinkButton
                            to={`/payments/create?party_id=${invoice.party?.id ?? ''}&invoice_id=${invoice.id}`}
                            className="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"
                        >
                            Receive Payment
                        </LinkButton>
                    )}
                </div>
            </div>

            {/* Editable Fields Drawer (when editing is toggled) */}
            {isEditing && (
                <div className="print:hidden bg-amber-50 border border-amber-200 rounded-xl p-5 space-y-5">
                    <div className="flex justify-between items-center border-b border-amber-200 pb-2">
                        <h4 className="text-xs font-bold text-amber-900 uppercase tracking-wider">
                            Customize Renter, Vehicle & Odometer Readings for this Invoice
                        </h4>
                        {hasInvoiceLines && (
                            <label className="flex items-center gap-2 text-xs text-amber-950 font-medium cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={useCustomCalculator}
                                    onChange={(e) => setUseCustomCalculator(e.target.checked)}
                                    className="rounded border-amber-300 text-amber-600"
                                />
                                Enable Custom Line Charges & Rates
                            </label>
                        )}
                    </div>

                    {/* Section 1 & 2: Parties, Vehicle & Odometer */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div>
                            <label className="block text-slate-600 font-medium mb-1">Driver Name</label>
                            <input
                                aria-label="Driver Name"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5"
                                value={driverName}
                                onChange={(e) => setDriverName(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-slate-600 font-medium mb-1">NIC / Passport</label>
                            <input
                                aria-label="NIC / Passport"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5"
                                value={driverNic}
                                onChange={(e) => setDriverNic(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-slate-600 font-medium mb-1">Driver License No.</label>
                            <input
                                aria-label="Driver License No."
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5"
                                value={driverLicense}
                                onChange={(e) => setDriverLicense(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-slate-600 font-medium mb-1">License Expiry</label>
                            <input
                                aria-label="License Expiry"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5"
                                value={licenseExpiry}
                                onChange={(e) => setLicenseExpiry(e.target.value)}
                            />
                        </div>

                        <div>
                            <label className="block text-slate-600 font-medium mb-1">Vehicle Make & Model</label>
                            <input
                                aria-label="Vehicle Make & Model"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5"
                                value={vehicleModel}
                                onChange={(e) => setVehicleModel(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-slate-600 font-medium mb-1">Plate / Registration</label>
                            <input
                                aria-label="Plate / Registration"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5"
                                value={vehiclePlate}
                                onChange={(e) => setVehiclePlate(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-slate-600 font-medium mb-1">Rental Mode</label>
                            <input
                                aria-label="Rental Mode"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5"
                                value={rentalMode}
                                onChange={(e) => setRentalMode(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-slate-600 font-medium mb-1">Rental Days</label>
                            <input
                                aria-label="Rental Days"
                                type="number"
                                min="1"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 font-mono"
                                value={rentalDays}
                                onChange={(e) => {
                                    const d = Math.max(1, Number(e.target.value) || 1);
                                    setRentalDays(d);
                                    setDriverDays(d);
                                    setCdwDays(d);
                                    setAddonDays(d);
                                }}
                            />
                        </div>

                        <div>
                            <label className="block text-slate-600 font-medium mb-1">Start Odometer (km)</label>
                            <input
                                aria-label="Start Odometer (km)"
                                type="number"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 font-mono"
                                value={startOdo}
                                onChange={(e) => setStartOdo(Number(e.target.value) || 0)}
                            />
                        </div>
                        <div>
                            <label className="block text-slate-600 font-medium mb-1">Return Odometer (km)</label>
                            <input
                                aria-label="Return Odometer (km)"
                                type="number"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 font-mono"
                                value={returnOdo}
                                onChange={(e) => setReturnOdo(Number(e.target.value) || 0)}
                            />
                        </div>
                        <div>
                            <label className="block text-slate-600 font-medium mb-1">Free Allowance (km/day)</label>
                            <input
                                aria-label="Free Allowance (km/day)"
                                type="number"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 font-mono"
                                value={includedKmPerDay}
                                onChange={(e) => setIncludedKmPerDay(Number(e.target.value) || 0)}
                            />
                        </div>
                        <div>
                            <label className="block text-slate-600 font-medium mb-1">Security Deposit Credit</label>
                            <input
                                aria-label="Security Deposit Credit"
                                className="w-full bg-white border border-slate-300 rounded px-2.5 py-1.5 font-mono font-semibold text-emerald-800"
                                value={depositCredit}
                                onChange={(e) => setDepositCredit(e.target.value)}
                                placeholder="0.00"
                            />
                        </div>
                    </div>

                    {/* Section 3: Itemized Charge Rates (When in custom calculation mode) */}
                    {useCustomCalculator && (
                        <div className="border-t border-amber-200/80 pt-3">
                            <h5 className="text-[11px] font-bold text-amber-900 uppercase tracking-wider mb-2">
                                Itemized Rates & Parameters ({currencySymbol})
                            </h5>
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 text-xs">
                                <div>
                                    <label className="block text-slate-600 text-[11px] mb-0.5">Daily Hire Rate</label>
                                    <input
                                        type="number"
                                        className="w-full bg-white border border-slate-300 rounded px-2 py-1 font-mono text-xs"
                                        value={baseDailyRate}
                                        onChange={(e) => setBaseDailyRate(Number(e.target.value) || 0)}
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 text-[11px] mb-0.5">Excess KM Rate</label>
                                    <input
                                        type="number"
                                        className="w-full bg-white border border-slate-300 rounded px-2 py-1 font-mono text-xs"
                                        value={excessKmRate}
                                        onChange={(e) => setExcessKmRate(Number(e.target.value) || 0)}
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 text-[11px] mb-0.5">Driver Rate/Day</label>
                                    <input
                                        type="number"
                                        className="w-full bg-white border border-slate-300 rounded px-2 py-1 font-mono text-xs"
                                        value={driverDailyRate}
                                        onChange={(e) => setDriverDailyRate(Number(e.target.value) || 0)}
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 text-[11px] mb-0.5">Fuel Price/Litre</label>
                                    <input
                                        type="number"
                                        className="w-full bg-white border border-slate-300 rounded px-2 py-1 font-mono text-xs"
                                        value={fuelPricePerLitre}
                                        onChange={(e) => setFuelPricePerLitre(Number(e.target.value) || 0)}
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 text-[11px] mb-0.5">CDW Rate/Day</label>
                                    <input
                                        type="number"
                                        className="w-full bg-white border border-slate-300 rounded px-2 py-1 font-mono text-xs"
                                        value={cdwDailyRate}
                                        onChange={(e) => setCdwDailyRate(Number(e.target.value) || 0)}
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 text-[11px] mb-0.5">VAT Rate (%)</label>
                                    <input
                                        type="number"
                                        step="0.1"
                                        className="w-full bg-white border border-slate-300 rounded px-2 py-1 font-mono text-xs"
                                        value={vatPercent}
                                        onChange={(e) => setVatPercent(Number(e.target.value) || 0)}
                                    />
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Printable Tax Invoice Card */}
            <div className="rental-invoice-sheet bg-white border border-slate-200 rounded-2xl p-8 sm:p-12 shadow-sm text-slate-800 print:border-none print:shadow-none print:p-0">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start border-b border-slate-200 pb-8 gap-6">
                    <div>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-blue-600 text-white rounded-lg flex items-center justify-center font-bold text-xl tracking-wider">
                                VR
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-slate-900 tracking-tight">AutoERP Rental Fleet Ltd.</h1>
                                <p className="text-xs text-slate-500">Vehicle Rental & Logistics Management</p>
                            </div>
                        </div>
                        <div className="mt-4 text-xs text-slate-600 space-y-0.5">
                            <p>120 Galle Road, Colombo 03, Sri Lanka</p>
                            <p>Phone: +94 11 234 5678 | Email: rentals@autoerp.lk</p>
                            <p>VAT Registration No: <span className="font-mono font-semibold text-slate-800">104859231-7000</span></p>
                            <p>Business Reg No: <span className="font-mono font-semibold text-slate-800">PV-98741</span></p>
                        </div>
                    </div>

                    <div className="sm:text-right">
                        <span className="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold tracking-wide uppercase mb-2">
                            TAX INVOICE
                        </span>
                        <div className="text-xl font-mono font-bold text-slate-900">{invoice.invoice_number || `INV-${invoice.id}`}</div>
                        <div className="mt-2 text-xs text-slate-600 space-y-0.5">
                            <p><span className="text-slate-400">Invoice Date:</span> <span className="font-medium text-slate-800">{formatDate(invoice.invoice_date)}</span></p>
                            <p><span className="text-slate-400">Due Date:</span> <span className="font-medium text-slate-800">{formatDate(invoice.due_date)}</span></p>
                            <p><span className="text-slate-400">Rental Reference:</span> <span className="font-mono font-medium text-slate-800">{invoice.notes || 'Rental Vehicle Settlement'}</span></p>
                        </div>
                    </div>
                </div>

                {/* 1. Customer & Renter Details & 2. Vehicle Specifications */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 my-8">
                    {/* 1. Customer Details */}
                    <div className="bg-slate-50 rounded-xl p-5 border border-slate-200">
                        <h2 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <span className="w-2 h-2 rounded-full bg-blue-600"></span>
                            1. Customer & Authorized Renter Details
                        </h2>
                        <div className="space-y-1.5 text-xs text-slate-700">
                            <div>
                                <div className="font-bold text-slate-900 text-sm">{partyName}</div>
                                {invoice.party?.code && <div className="text-slate-500">Client Code: {String(invoice.party.code)}</div>}
                            </div>
                            <div className="pt-1"><span className="text-slate-500">Billing Address:</span> {partyAddress}</div>
                            <div><span className="text-slate-500">Contact / Email:</span> {partyPhone} | {partyEmail}</div>
                            <div><span className="text-slate-500">Tax / VAT ID:</span> <span className="font-mono font-medium text-slate-800">{partyTaxNumber}</span></div>

                            <div className="border-t border-slate-200 mt-2.5 pt-2 text-[11px] space-y-1">
                                <div className="font-semibold text-slate-800">Designated Driver / Renter:</div>
                                <div className="grid grid-cols-2 gap-2">
                                    <div><span className="text-slate-500">Name:</span> <span className="font-medium text-slate-900">{driverName}</span></div>
                                    <div><span className="text-slate-500">NIC / Passport:</span> <span className="font-mono font-medium text-slate-900">{driverNic}</span></div>
                                    <div><span className="text-slate-500">Driver License:</span> <span className="font-mono font-medium text-slate-900">{driverLicense}</span></div>
                                    <div><span className="text-slate-500">Expiry:</span> <span className="font-medium text-slate-900">{licenseExpiry}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* 2. Vehicle Specifications & Odometer */}
                    <div className="bg-slate-50 rounded-xl p-5 border border-slate-200">
                        <h2 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <span className="w-2 h-2 rounded-full bg-blue-600"></span>
                            2. Vehicle Specifications & Mileage
                        </h2>
                        <div className="space-y-1.5 text-xs text-slate-700">
                            <div className="flex justify-between items-center">
                                <div>
                                    <div className="font-bold text-slate-900 text-sm">{vehicleModel}</div>
                                    <div className="text-slate-500">Rental Mode: {rentalMode}</div>
                                </div>
                                <div className="text-right">
                                    <span className="inline-block px-2.5 py-1 bg-slate-900 text-white font-mono font-bold rounded text-xs tracking-wider">
                                        {vehiclePlate}
                                    </span>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200">
                                <div><span className="text-slate-500">Hire Start:</span> <span className="font-medium text-slate-900">{startDate}</span></div>
                                <div><span className="text-slate-500">Hire Return:</span> <span className="font-medium text-slate-900">{returnDate}</span></div>
                                <div><span className="text-slate-500">Duration:</span> <span className="font-medium text-slate-900">{rentalDays} Full Days</span></div>
                                <div><span className="text-slate-500">Fuel Level:</span> <span className="font-medium text-slate-900">100% Out / 65% In</span></div>
                            </div>

                            <div className="mt-2.5 p-2.5 bg-blue-50/70 border border-blue-200/60 rounded-lg text-xs space-y-1">
                                <div className="grid grid-cols-3 text-center divide-x divide-blue-200">
                                    <div>
                                        <div className="text-[10px] text-slate-500 uppercase">Start Odo</div>
                                        <div className="font-mono font-bold text-slate-800">{startOdo.toLocaleString()} km</div>
                                    </div>
                                    <div>
                                        <div className="text-[10px] text-slate-500 uppercase">Return Odo</div>
                                        <div className="font-mono font-bold text-slate-800">{returnOdo.toLocaleString()} km</div>
                                    </div>
                                    <div>
                                        <div className="text-[10px] text-blue-700 font-semibold uppercase">Total Driven</div>
                                        <div className="font-mono font-bold text-blue-900">{totalDrivenKm.toLocaleString()} km</div>
                                    </div>
                                </div>
                                <div className="flex justify-between text-[11px] pt-1 border-t border-blue-100 text-slate-600 px-1">
                                    <span>Included: {totalIncludedKm.toLocaleString()} km ({includedKmPerDay} km/day)</span>
                                    <span className="font-semibold text-amber-700">Excess: {billableExcessKm.toLocaleString()} km</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* 3. Itemized Charges Breakdown */}
                <div className="my-6">
                    <h2 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <span className="w-2 h-2 rounded-full bg-blue-600"></span>
                        3. Itemized Charges Breakdown
                    </h2>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr className="border-b-2 border-slate-300 bg-slate-50 text-slate-600 uppercase font-semibold text-[11px]">
                                    <th className="py-3 px-3">#</th>
                                    <th className="py-3 px-3">Description & Charge Details</th>
                                    <th className="py-3 px-3 text-right">Qty</th>
                                    <th className="py-3 px-3 text-right">Rate ({currencySymbol})</th>
                                    <th className="py-3 px-3 text-right">Tax ({currencySymbol})</th>
                                    <th className="py-3 px-3 text-right">Line Total ({currencySymbol})</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 text-slate-700">
                                {!useCustomCalculator && hasInvoiceLines && invoice.lines ? (
                                    invoice.lines.map((line, idx) => (
                                        <tr key={line.id || idx}>
                                            <td className="py-3 px-3 font-mono text-slate-400">{String(idx + 1).padStart(2, '0')}</td>
                                            <td className="py-3 px-3">
                                                <span className="font-semibold text-slate-900 block">{line.description}</span>
                                                {line.item && <span className="text-slate-500 text-[11px]">Item: {line.item.name || line.item.code}</span>}
                                            </td>
                                            <td className="py-3 px-3 text-right font-mono">{formatCurrency(line.quantity)}</td>
                                            <td className="py-3 px-3 text-right font-mono">{formatCurrency(line.unit_price)}</td>
                                            <td className="py-3 px-3 text-right font-mono">{formatCurrency(line.tax_amount)}</td>
                                            <td className="py-3 px-3 text-right font-mono font-semibold">{formatCurrency(line.line_total)}</td>
                                        </tr>
                                    ))
                                ) : (
                                    calculatedLineItems.map((item) => (
                                        <tr key={item.id}>
                                            <td className="py-3 px-3 font-mono text-slate-400">{item.code}</td>
                                            <td className="py-3 px-3">
                                                <span className="font-semibold text-slate-900 block">{item.description}</span>
                                                <span className="text-slate-500 text-[11px]">{item.subDescription}</span>
                                            </td>
                                            <td className="py-3 px-3 text-right font-mono">{formatCurrency(item.quantity)}</td>
                                            <td className="py-3 px-3 text-right font-mono">{formatCurrency(item.unitPrice)}</td>
                                            <td className="py-3 px-3 text-right font-mono">{formatCurrency(item.taxAmount)}</td>
                                            <td className="py-3 px-3 text-right font-mono font-semibold">{formatCurrency(item.lineTotal)}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Financial Totals */}
                    <div className="mt-6 flex flex-col sm:flex-row justify-end">
                        <div className="w-full sm:w-80 space-y-2 text-xs">
                            <div className="flex justify-between text-slate-600">
                                <span>Subtotal (Net Charges):</span>
                                <span className="font-mono font-medium text-slate-900">{currencySymbol} {formatCurrency(subtotal)}</span>
                            </div>

                            <div className="flex justify-between text-slate-600 items-center">
                                <span>Value Added Tax (VAT {vatPercent.toFixed(2)}%):</span>
                                <span className="font-mono font-medium text-slate-900">{currencySymbol} {formatCurrency(taxTotal)}</span>
                            </div>

                            <div className="border-t border-slate-300 pt-2 flex justify-between text-slate-900 font-bold text-sm">
                                <span>Gross Invoice Total:</span>
                                <span className="font-mono text-blue-900">{currencySymbol} {formatCurrency(grossTotal)}</span>
                            </div>

                            {/* Security deposit credit */}
                            {depositNumber > 0 && (
                                <div className="bg-amber-50 border border-amber-200/80 rounded-lg p-2.5 my-2 space-y-1">
                                    <div className="flex justify-between text-amber-900 font-medium text-[11px]">
                                        <span>Security Deposit Received / Credit:</span>
                                        <span className="font-mono font-semibold">- {currencySymbol} {formatCurrency(depositNumber)}</span>
                                    </div>
                                    <div className="text-[10px] text-amber-700 leading-tight">
                                        Adjusted against rental invoice. Remaining balance refundable upon final vehicle return inspection.
                                    </div>
                                </div>
                            )}

                            {refundDue > 0 ? (
                                <div className="border-t-2 border-emerald-600 pt-2 flex justify-between text-emerald-800 font-extrabold text-base">
                                    <span>Refund Due to Customer:</span>
                                    <span className="font-mono text-emerald-700">{currencySymbol} {formatCurrency(refundDue)}</span>
                                </div>
                            ) : (
                                <div className="border-t-2 border-slate-900 pt-2 flex justify-between text-slate-900 font-extrabold text-base">
                                    <span>Net Balance Payable:</span>
                                    <span className="font-mono text-emerald-700">{currencySymbol} {formatCurrency(netBalance)}</span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* 4. Terms, Payment Details & Signatures */}
                <div className="border-t border-slate-200 pt-6 mt-8">
                    <h2 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                        4. Payment Methods & Terms of Agreement
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-slate-600 mb-8">
                        {/* Payment details */}
                        <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-1">
                            <div className="font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                Bank Remittance & Payment Details
                            </div>
                            <div><span className="text-slate-500">Bank Name:</span> Commercial Bank of Ceylon PLC</div>
                            <div><span className="text-slate-500">Account Name:</span> AutoERP Rental Fleet Ltd</div>
                            <div><span className="text-slate-500">Account No:</span> <span className="font-mono font-semibold text-slate-800">1000 2489 3920</span></div>
                            <div><span className="text-slate-500">Branch / Swift:</span> Corporate Branch / CCEYLKLX</div>
                            <div><span className="text-slate-500">Payment Reference:</span> <span className="font-mono font-bold text-blue-700">{invoice.invoice_number || `INV-${invoice.id}`}</span></div>
                            <div className="text-[11px] text-slate-500 pt-1">Cash, Corporate Cheque, or Visa/MasterCard payments accepted at our depot counter.</div>
                        </div>

                        {/* Rental policies */}
                        <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-1 text-[11px] leading-relaxed">
                            <div className="font-bold text-slate-800 mb-1">Terms & Operating Conditions</div>
                            <ul className="list-disc pl-4 space-y-1">
                                <li><strong>Grace Period & Late Returns:</strong> A 1-hour grace period applies for vehicle return. Subsequent delays are billed at LKR 1,500/hour or a full daily rate if exceeding 3 hours.</li>
                                <li><strong>Damage & CDW Terms:</strong> CDW covers accidental collision damage subject to a maximum excess deductible of LKR 25,000. Undercarriage and interior damage are excluded.</li>
                                <li><strong>Traffic Violations:</strong> Any speeding or parking fines incurred during the hire period remain the sole legal responsibility of the lessee.</li>
                            </ul>
                        </div>
                    </div>

                    {/* Signatures */}
                    <div className="grid grid-cols-2 gap-12 pt-6 border-t border-slate-200 text-xs">
                        <div>
                            <div className="h-14 border-b border-dashed border-slate-400 flex items-end pb-1">
                                <span className="text-slate-500 italic text-sm">Authorized Fleet Officer</span>
                            </div>
                            <div className="mt-2 font-bold text-slate-800">Authorized Signature (Lessor)</div>
                            <div className="text-[11px] text-slate-500">AutoERP Rental Fleet Ltd.</div>
                            <div className="text-[10px] text-slate-400 mt-0.5">Date: {formatDate(invoice.invoice_date)}</div>
                        </div>

                        <div>
                            <div className="h-14 border-b border-dashed border-slate-400 flex items-end pb-1">
                                <span className="text-slate-500 italic text-sm">{partyName}</span>
                            </div>
                            <div className="mt-2 font-bold text-slate-800">Customer / Renter Signature (Lessee)</div>
                            <div className="text-[11px] text-slate-500">I accept all listed charges, odometer readings & terms.</div>
                            <div className="text-[10px] text-slate-400 mt-0.5">Date: {formatDate(invoice.invoice_date)}</div>
                        </div>
                    </div>
                </div>

                {/* Footer Note */}
                <div className="mt-8 pt-4 border-t border-slate-100 text-center text-[11px] text-slate-400">
                    Thank you for choosing AutoERP Rental Fleet! For 24/7 Roadside Assistance, call <span className="font-semibold text-slate-600">+94 77 999 8888</span>.
                </div>
            </div>
        </div>
    );
}
