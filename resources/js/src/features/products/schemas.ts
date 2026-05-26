import { z } from 'zod';

function emptyToUndefined(value: unknown) {
    if (value === '' || value === null || value === undefined) {
        return undefined;
    }

    if (typeof value === 'string') {
        const trimmed = value.trim();
        return trimmed === '' ? undefined : trimmed;
    }

    return value;
}

function optionalInteger(message = 'Enter a valid value.') {
    return z.preprocess((value) => {
        const normalized = emptyToUndefined(value);
        if (normalized === undefined) {
            return undefined;
        }

        return Number(normalized);
    }, z.number().int(message).positive(message).optional());
}

function requiredInteger(message: string) {
    return z.preprocess((value) => {
        const normalized = emptyToUndefined(value);
        if (normalized === undefined) {
            return undefined;
        }

        return Number(normalized);
    }, z.number().int(message).positive(message));
}

function optionalDecimal(message = 'Enter a valid number.') {
    return z.preprocess((value) => {
        const normalized = emptyToUndefined(value);
        if (normalized === undefined) {
            return undefined;
        }

        return Number(normalized);
    }, z.number(message).finite(message).optional());
}

function optionalTrimmedString(maxLength: number, message: string) {
    return z.preprocess(emptyToUndefined, z.string().max(maxLength, message).optional());
}

export const productFormSchema = z
    .object({
        type: z.enum(['physical', 'service', 'digital', 'combo', 'variable'], {
            message: 'Product type is required.',
        }),
        name: z.string().trim().min(1, 'Product name is required.').max(255, 'Name must be 255 characters or less.'),
        slug: optionalTrimmedString(255, 'Slug must be 255 characters or less.'),
        sku: optionalTrimmedString(255, 'SKU must be 255 characters or less.'),
        description: optionalTrimmedString(2000, 'Description must be 2000 characters or less.'),
        category_id: optionalInteger('Choose a valid category.'),
        brand_id: optionalInteger('Choose a valid brand.'),
        base_uom_id: requiredInteger('Base UOM is required.'),
        purchase_uom_id: optionalInteger('Choose a valid purchase UOM.'),
        sales_uom_id: optionalInteger('Choose a valid sales UOM.'),
        uom_conversion_factor: optionalDecimal('Enter a valid conversion factor.'),
        valuation_method: z
            .enum(['fifo', 'lifo', 'weighted_average', 'standard'], { message: 'Choose a valuation method.' })
            .optional(),
        standard_cost: optionalDecimal('Enter a valid standard cost.'),
        purchase_price: optionalDecimal('Enter a valid purchase price.'),
        sales_price: optionalDecimal('Enter a valid sales price.'),
        profit_margin: optionalDecimal('Enter a valid profit margin.'),
        price_list_note: optionalTrimmedString(500, 'Price note must be 500 characters or less.'),
        supplier_reference: optionalTrimmedString(255, 'Supplier reference must be 255 characters or less.'),
        identifier_technology: z
            .enum(['barcode_1d', 'barcode_2d', 'qr_code', 'rfid_hf', 'rfid_uhf', 'nfc', 'gs1_epc', 'custom'], {
                message: 'Choose a valid identifier technology.',
            })
            .optional(),
        identifier_format: z
            .enum(['ean13', 'ean8', 'upc_a', 'code128', 'code39', 'qr', 'datamatrix', 'gs1_128', 'epc_sgtin', 'other'], {
                message: 'Choose a valid identifier format.',
            })
            .optional(),
        identifier_value: optionalTrimmedString(255, 'Identifier value must be 255 characters or less.'),
        identifier_gs1_company_prefix: optionalTrimmedString(255, 'GS1 company prefix must be 255 characters or less.'),
        identifier_is_primary: z.boolean().default(true),
        identifier_is_active: z.boolean().default(true),
        is_batch_tracked: z.boolean().default(false),
        is_lot_tracked: z.boolean().default(false),
        is_serial_tracked: z.boolean().default(false),
        is_active: z.boolean().default(true),
    })
    .superRefine((value, context) => {
        if (value.is_serial_tracked && (value.is_batch_tracked || value.is_lot_tracked)) {
            context.addIssue({
                code: z.ZodIssueCode.custom,
                message: 'Serial tracking cannot be combined with batch or lot tracking.',
                path: ['is_serial_tracked'],
            });
        }

        if (value.valuation_method === 'standard' && value.standard_cost === undefined) {
            context.addIssue({
                code: z.ZodIssueCode.custom,
                message: 'Standard cost is required when valuation method is standard.',
                path: ['standard_cost'],
            });
        }

        if (value.identifier_value && !value.identifier_technology) {
            context.addIssue({
                code: z.ZodIssueCode.custom,
                message: 'Identifier technology is required when an identifier value is entered.',
                path: ['identifier_technology'],
            });
        }
    });

export const taxonomyFormSchema = z.object({
    name: z.string().trim().min(1, 'Name is required.').max(255, 'Name must be 255 characters or less.'),
    slug: optionalTrimmedString(255, 'Slug must be 255 characters or less.'),
    code: optionalTrimmedString(100, 'Code must be 100 characters or less.'),
    parent_id: optionalInteger('Choose a valid parent record.'),
    website: z.preprocess(emptyToUndefined, z.url('Enter a valid website URL.').max(255, 'Website must be 255 characters or less.').optional()),
    description: optionalTrimmedString(2000, 'Description must be 2000 characters or less.'),
    is_active: z.boolean().default(true),
});

export const unitOfMeasureFormSchema = z.object({
    name: z.string().trim().min(1, 'Name is required.').max(255, 'Name must be 255 characters or less.'),
    symbol: z.string().trim().min(1, 'Symbol is required.').max(50, 'Symbol must be 50 characters or less.'),
    type: z.enum(['unit', 'mass', 'volume', 'length', 'time', 'other'], { message: 'Choose a valid type.' }),
    is_base: z.boolean().default(false),
});

export const productVariantFormSchema = z.object({
    name: z.string().trim().min(1, 'Variant name is required.').max(255, 'Variant name must be 255 characters or less.'),
    sku: optionalTrimmedString(255, 'SKU must be 255 characters or less.'),
    attribute_summary: optionalTrimmedString(255, 'Attribute summary must be 255 characters or less.'),
    notes: optionalTrimmedString(500, 'Notes must be 500 characters or less.'),
    is_default: z.boolean().default(false),
    is_active: z.boolean().default(true),
});

export const productIdentifierFormSchema = z.object({
    technology: z.enum(['barcode_1d', 'barcode_2d', 'qr_code', 'rfid_hf', 'rfid_uhf', 'nfc', 'gs1_epc', 'custom'], {
        message: 'Technology is required.',
    }),
    format: z
        .enum(['ean13', 'ean8', 'upc_a', 'code128', 'code39', 'qr', 'datamatrix', 'gs1_128', 'epc_sgtin', 'other'], {
            message: 'Choose a valid format.',
        })
        .optional(),
    value: z.string().trim().min(1, 'Identifier value is required.').max(255, 'Identifier value must be 255 characters or less.'),
    gs1_company_prefix: optionalTrimmedString(255, 'GS1 company prefix must be 255 characters or less.'),
    is_primary: z.boolean().default(false),
    is_active: z.boolean().default(true),
});

export const uomConversionFormSchema = z.object({
    from_uom_id: requiredInteger('From UOM is required.'),
    to_uom_id: requiredInteger('To UOM is required.'),
    factor: z.preprocess((value) => {
        const normalized = emptyToUndefined(value);
        if (normalized === undefined) {
            return undefined;
        }

        return Number(normalized);
    }, z.number().positive('Conversion factor must be greater than zero.')),
});

export type ProductFormInput = z.input<typeof productFormSchema>;
export type ProductFormValues = z.output<typeof productFormSchema>;
export type TaxonomyFormInput = z.input<typeof taxonomyFormSchema>;
export type TaxonomyFormValues = z.output<typeof taxonomyFormSchema>;
export type UnitOfMeasureFormInput = z.input<typeof unitOfMeasureFormSchema>;
export type UnitOfMeasureFormValues = z.output<typeof unitOfMeasureFormSchema>;
export type ProductVariantFormInput = z.input<typeof productVariantFormSchema>;
export type ProductVariantFormValues = z.output<typeof productVariantFormSchema>;
export type ProductIdentifierFormInput = z.input<typeof productIdentifierFormSchema>;
export type ProductIdentifierFormValues = z.output<typeof productIdentifierFormSchema>;
export type UomConversionFormInput = z.input<typeof uomConversionFormSchema>;
export type UomConversionFormValues = z.output<typeof uomConversionFormSchema>;
