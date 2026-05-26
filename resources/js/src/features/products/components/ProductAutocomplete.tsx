import { useEffect, useId, useMemo, useRef, useState, type KeyboardEvent } from 'react';
import { cn } from '../../../lib/cn';
import type { Product } from '../types';

type ProductAutocompleteProps = {
    products: Product[];
    value: Product | null;
    onChange: (product: Product | null) => void;
    placeholder?: string;
    disabled?: boolean;
    isLoading?: boolean;
    error?: string;
};

type ProductWithOptionalSearchFields = Product & {
    barcode?: string | null;
    code?: string | null;
    identifiers?: Array<{ value?: string | null }>;
};

function optionalText(value: unknown): string {
    return typeof value === 'string' || typeof value === 'number' ? String(value) : '';
}

function productLabel(product: Product): string {
    return product.sku ? `${product.name} (${product.sku})` : product.name;
}

function productSearchText(product: Product): string {
    const searchableProduct = product as ProductWithOptionalSearchFields;
    const metadata = product.metadata ?? {};
    const identifierValues = searchableProduct.identifiers?.map((identifier) => identifier.value).filter(Boolean).join(' ') ?? '';

    return [
        product.name,
        product.sku,
        searchableProduct.code,
        searchableProduct.barcode,
        metadata.barcode,
        metadata.bar_code,
        metadata.code,
        metadata.sku,
        identifierValues,
    ]
        .map(optionalText)
        .join(' ')
        .toLowerCase();
}

export function ProductAutocomplete({ disabled = false, error, isLoading = false, onChange, placeholder = 'Search product by name, SKU, or barcode', products, value }: ProductAutocompleteProps) {
    const listboxId = useId();
    const [inputValue, setInputValue] = useState(value ? productLabel(value) : '');
    const [isOpen, setIsOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);
    const rootRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        setInputValue(value ? productLabel(value) : '');
    }, [value]);

    useEffect(() => {
        function handlePointerDown(event: MouseEvent) {
            if (!rootRef.current?.contains(event.target as Node)) {
                setIsOpen(false);
            }
        }

        document.addEventListener('mousedown', handlePointerDown);

        return () => document.removeEventListener('mousedown', handlePointerDown);
    }, []);

    const filteredProducts = useMemo(() => {
        const search = inputValue.trim().toLowerCase();

        if (!search || value) {
            return products.slice(0, 20);
        }

        return products.filter((product) => productSearchText(product).includes(search)).slice(0, 20);
    }, [inputValue, products, value]);

    useEffect(() => {
        setActiveIndex(0);
    }, [inputValue, filteredProducts.length]);

    function selectProduct(product: Product) {
        onChange(product);
        setInputValue(productLabel(product));
        setIsOpen(false);
    }

    function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setIsOpen(true);
            setActiveIndex((current) => (filteredProducts.length > 0 ? (current + 1) % filteredProducts.length : 0));
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setIsOpen(true);
            setActiveIndex((current) => (filteredProducts.length > 0 ? (current - 1 + filteredProducts.length) % filteredProducts.length : 0));
            return;
        }

        if (event.key === 'Enter' && isOpen) {
            event.preventDefault();
            const product = filteredProducts[activeIndex];

            if (product) {
                selectProduct(product);
            }
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            setIsOpen(false);
        }
    }

    return (
        <div className="relative" ref={rootRef}>
            <input
                aria-activedescendant={isOpen && filteredProducts[activeIndex] ? `${listboxId}-${filteredProducts[activeIndex].id}` : undefined}
                aria-autocomplete="list"
                aria-controls={listboxId}
                aria-expanded={isOpen}
                className={cn(
                    'h-11 w-full rounded-xl border border-stone-200 bg-white px-3 text-sm text-stone-900 shadow-xs outline-none transition',
                    'placeholder:text-stone-400 focus:border-stone-400 focus:ring-2 focus:ring-stone-200',
                    error && 'border-red-300 focus:border-red-400 focus:ring-red-100',
                )}
                disabled={disabled}
                onChange={(event) => {
                    setInputValue(event.target.value);
                    setIsOpen(true);

                    if (value) {
                        onChange(null);
                    }
                }}
                onFocus={() => setIsOpen(true)}
                onKeyDown={handleKeyDown}
                placeholder={isLoading ? 'Loading products...' : placeholder}
                role="combobox"
                type="text"
                value={inputValue}
            />
            {isOpen && !disabled ? (
                <div className="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-stone-200 bg-white py-1 text-sm shadow-lg" id={listboxId} role="listbox">
                    {filteredProducts.length > 0 ? (
                        filteredProducts.map((product, index) => {
                            const isActive = index === activeIndex;

                            return (
                                <button
                                    aria-selected={isActive}
                                    className={cn('flex w-full flex-col px-3 py-2 text-left transition', isActive ? 'bg-stone-100 text-stone-950' : 'text-stone-700 hover:bg-stone-50')}
                                    id={`${listboxId}-${product.id}`}
                                    key={product.id}
                                    onMouseDown={(event) => event.preventDefault()}
                                    onMouseEnter={() => setActiveIndex(index)}
                                    onClick={() => selectProduct(product)}
                                    role="option"
                                    type="button"
                                >
                                    <span className="font-medium">{product.name}</span>
                                    {product.sku ? <span className="text-xs text-stone-500">{product.sku}</span> : null}
                                </button>
                            );
                        })
                    ) : (
                        <div className="px-3 py-3 text-sm text-stone-500">No products found</div>
                    )}
                </div>
            ) : null}
        </div>
    );
}
