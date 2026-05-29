import { Select } from '../ui/Select';

const options = [
    { label: 'Each', value: 'each' },
    { label: 'Hour', value: 'hour' },
    { label: 'Liter', value: 'liter' },
];

export function UomSelector() {
    return <Select options={options} placeholder="Select UOM" />;
}
