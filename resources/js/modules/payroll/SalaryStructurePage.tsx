/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useCallback, useEffect, useState } from 'react';
import {
    createComponent,
    createStructure,
    deleteStructure,
    listComponents,
    listStructures,
} from './payrollApi';
import type {
    CalculationType,
    PayrollCycleType,
    SalaryComponent,
    SalaryComponentType,
    SalaryStructure,
    SalaryStructureItem,
} from './payrollTypes';

export default function SalaryStructurePage() {
    const [structures, setStructures] = useState<SalaryStructure[]>([]);
    const [components, setComponents] = useState<SalaryComponent[]>([]);
    const [loading, setLoading] = useState(true);

    // New Structure Form State
    const [showStructureModal, setShowStructureModal] = useState(false);
    const [structCode, setStructCode] = useState('');
    const [structName, setStructName] = useState('');
    const [cycleType, setCycleType] = useState<PayrollCycleType>('monthly');
    const [structItems, setStructItems] = useState<SalaryStructureItem[]>([]);

    // New Component Form State
    const [showComponentModal, setShowComponentModal] = useState(false);
    const [compCode, setCompCode] = useState('');
    const [compName, setCompName] = useState('');
    const [compType, setCompType] = useState<SalaryComponentType>('earning');
    const [compCalcType, setCompCalcType] = useState<CalculationType>('fixed');
    const [compTaxable, setCompTaxable] = useState(true);
    const [compStatutory, setCompStatutory] = useState(true);

    const [actionLoading, setActionLoading] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    const loadData = useCallback(() => {
        Promise.all([listStructures(), listComponents()])
            .then(([sData, cData]) => {
                setStructures(sData);
                setComponents(cData);
            })
            .catch((err) => setErrorMsg(err.message || 'Failed to load salary structures.'))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        loadData();
    }, [loadData]);

    const handleAddItem = () => {
        if (components.length === 0) return;
        setStructItems([
            ...structItems,
            {
                salary_component_id: components[0].id,
                calculation_type: 'fixed',
                amount_or_rate: 0,
                sort_order: structItems.length + 1,
            },
        ]);
    };

    const handleRemoveItem = (index: number) => {
        setStructItems(structItems.filter((_, i) => i !== index));
    };

    const handleSaveStructure = async (e: React.FormEvent) => {
        e.preventDefault();
        setActionLoading(true);
        setErrorMsg(null);
        try {
            await createStructure({
                code: structCode,
                name: structName,
                cycle_type: cycleType,
                items: structItems,
            });
            setShowStructureModal(false);
            setStructCode('');
            setStructName('');
            setStructItems([]);
            loadData();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } }; message?: string };
            setErrorMsg(error.response?.data?.message || error.message || 'Failed to save salary structure.');
        } finally {
            setActionLoading(false);
        }
    };

    const handleSaveComponent = async (e: React.FormEvent) => {
        e.preventDefault();
        setActionLoading(true);
        setErrorMsg(null);
        try {
            await createComponent({
                code: compCode,
                name: compName,
                type: compType,
                calculation_type: compCalcType,
                is_taxable: compTaxable,
                is_statutory_base: compStatutory,
            });
            setShowComponentModal(false);
            setCompCode('');
            setCompName('');
            loadData();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } }; message?: string };
            setErrorMsg(error.response?.data?.message || error.message || 'Failed to save component.');
        } finally {
            setActionLoading(false);
        }
    };

    const handleDeleteStructure = async (id: number) => {
        if (!confirm('Are you sure you want to delete this salary structure?')) return;
        try {
            await deleteStructure(id);
            loadData();
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } }; message?: string };
            alert(error.response?.data?.message || error.message || 'Failed to delete structure.');
        }
    };

    return (
        <div className="space-y-6 p-6">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Salary Structures & Compensation
                    </h1>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Manage configurable salary templates, earning components, allowances, and statutory bases.
                    </p>
                </div>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => setShowComponentModal(true)}
                        className="rounded-md border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        + Add Component
                    </button>
                    <button
                        type="button"
                        onClick={() => setShowStructureModal(true)}
                        className="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        + Create Structure
                    </button>
                </div>
            </div>

            {errorMsg && (
                <div className="rounded-md bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
                    {errorMsg}
                </div>
            )}

            {loading ? (
                <div className="py-12 text-center text-sm text-gray-500">Loading payroll configuration...</div>
            ) : (
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {structures.map((structure) => (
                        <div
                            key={structure.id}
                            className="flex flex-col justify-between rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div>
                                <div className="flex items-center justify-between">
                                    <span className="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                        {structure.code}
                                    </span>
                                    <span className="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {structure.cycle_type}
                                    </span>
                                </div>
                                <h3 className="mt-2 text-lg font-bold text-gray-900 dark:text-gray-100">
                                    {structure.name}
                                </h3>
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {structure.description || 'No description provided.'}
                                </p>

                                <div className="mt-4 border-t border-gray-100 pt-3 dark:border-gray-700">
                                    <h4 className="text-xs font-semibold uppercase text-gray-400">Components ({structure.items?.length || 0})</h4>
                                    <ul className="mt-2 divide-y divide-gray-100 text-sm dark:divide-gray-700">
                                        {structure.items?.map((item) => (
                                            <li key={item.id} className="flex items-center justify-between py-1.5 text-xs">
                                                <span className="text-gray-700 dark:text-gray-300">
                                                    {item.component?.name || item.component?.code}
                                                </span>
                                                <span className="font-semibold text-gray-900 dark:text-gray-100">
                                                    {item.calculation_type === 'percentage'
                                                        ? `${item.amount_or_rate}% of Basic`
                                                        : `$${Number(item.amount_or_rate).toLocaleString()}`}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>

                            <div className="mt-5 border-t border-gray-100 pt-3 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => handleDeleteStructure(structure.id)}
                                    className="text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400"
                                >
                                    Delete Structure
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Structure Modal */}
            {showStructureModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h2 className="text-lg font-bold text-gray-900 dark:text-gray-100">Create Salary Structure</h2>
                        <form onSubmit={handleSaveStructure} className="mt-4 space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Code</label>
                                    <input
                                        type="text"
                                        required
                                        value={structCode}
                                        onChange={(e) => setStructCode(e.target.value.toUpperCase())}
                                        placeholder="e.g. EXEC_GRADE"
                                        className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>
                                <div>
                                    <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Name</label>
                                    <input
                                        type="text"
                                        required
                                        value={structName}
                                        onChange={(e) => setStructName(e.target.value)}
                                        placeholder="e.g. Executive Staff Structure"
                                        className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Payroll Cycle</label>
                                <select
                                    value={cycleType}
                                    onChange={(e) => setCycleType(e.target.value as PayrollCycleType)}
                                    className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                >
                                    <option value="monthly">Monthly</option>
                                    <option value="bi_weekly">Bi-Weekly</option>
                                    <option value="weekly">Weekly</option>
                                </select>
                            </div>

                            <div className="border-t border-gray-200 pt-3 dark:border-gray-700">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-sm font-semibold text-gray-800 dark:text-gray-200">Structure Components</h3>
                                    <button
                                        type="button"
                                        onClick={handleAddItem}
                                        className="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                                    >
                                        + Add Item
                                    </button>
                                </div>
                                <div className="mt-2 space-y-2 max-h-48 overflow-y-auto">
                                    {structItems.map((item, index) => (
                                        <div key={index} className="flex items-center gap-2 text-xs">
                                            <select
                                                value={item.salary_component_id}
                                                onChange={(e) => {
                                                    const newItems = [...structItems];
                                                    newItems[index].salary_component_id = Number(e.target.value);
                                                    setStructItems(newItems);
                                                }}
                                                className="w-1/2 rounded border border-gray-300 px-2 py-1 dark:border-gray-600 dark:bg-gray-700"
                                            >
                                                {components.map((c) => (
                                                    <option key={c.id} value={c.id}>
                                                        {c.code} - {c.name} ({c.type})
                                                    </option>
                                                ))}
                                            </select>
                                            <select
                                                value={item.calculation_type}
                                                onChange={(e) => {
                                                    const newItems = [...structItems];
                                                    newItems[index].calculation_type = e.target.value as CalculationType;
                                                    setStructItems(newItems);
                                                }}
                                                className="w-1/4 rounded border border-gray-300 px-2 py-1 dark:border-gray-600 dark:bg-gray-700"
                                            >
                                                <option value="fixed">Fixed</option>
                                                <option value="percentage">% of Basic</option>
                                            </select>
                                            <input
                                                type="number"
                                                value={item.amount_or_rate}
                                                onChange={(e) => {
                                                    const newItems = [...structItems];
                                                    newItems[index].amount_or_rate = Number(e.target.value);
                                                    setStructItems(newItems);
                                                }}
                                                placeholder="Amount / %"
                                                className="w-1/4 rounded border border-gray-300 px-2 py-1 dark:border-gray-600 dark:bg-gray-700"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => handleRemoveItem(index)}
                                                className="text-red-500 hover:text-red-700"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => setShowStructureModal(false)}
                                    className="rounded border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={actionLoading}
                                    className="rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    {actionLoading ? 'Saving...' : 'Save Structure'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Component Modal */}
            {showComponentModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h2 className="text-lg font-bold text-gray-900 dark:text-gray-100">Create Salary Component</h2>
                        <form onSubmit={handleSaveComponent} className="mt-4 space-y-4">
                            <div>
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Code</label>
                                <input
                                    type="text"
                                    required
                                    value={compCode}
                                    onChange={(e) => setCompCode(e.target.value.toUpperCase())}
                                    placeholder="e.g. COLA"
                                    className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Name</label>
                                <input
                                    type="text"
                                    required
                                    value={compName}
                                    onChange={(e) => setCompName(e.target.value)}
                                    placeholder="Cost of Living Allowance"
                                    className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Type</label>
                                    <select
                                        value={compType}
                                        onChange={(e) => setCompType(e.target.value as SalaryComponentType)}
                                        className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    >
                                        <option value="earning">Earning</option>
                                        <option value="deduction">Deduction</option>
                                        <option value="statutory">Statutory</option>
                                        <option value="employer_contribution">Employer Contribution</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="text-xs font-medium text-gray-700 dark:text-gray-300">Calculation</label>
                                    <select
                                        value={compCalcType}
                                        onChange={(e) => setCompCalcType(e.target.value as CalculationType)}
                                        className="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    >
                                        <option value="fixed">Fixed</option>
                                        <option value="percentage">Percentage</option>
                                    </select>
                                </div>
                            </div>

                            <div className="flex gap-4">
                                <label className="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                    <input
                                        type="checkbox"
                                        checked={compTaxable}
                                        onChange={(e) => setCompTaxable(e.target.checked)}
                                        className="rounded border-gray-300 text-indigo-600"
                                    />
                                    Taxable
                                </label>
                                <label className="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                    <input
                                        type="checkbox"
                                        checked={compStatutory}
                                        onChange={(e) => setCompStatutory(e.target.checked)}
                                        className="rounded border-gray-300 text-indigo-600"
                                    />
                                    Statutory Base
                                </label>
                            </div>

                            <div className="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => setShowComponentModal(false)}
                                    className="rounded border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={actionLoading}
                                    className="rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    {actionLoading ? 'Saving...' : 'Save Component'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
