import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach, beforeEach, vi } from 'vitest';

beforeEach(() => {
    Object.defineProperty(window, 'confirm', {
        configurable: true,
        writable: true,
        value: vi.fn(() => true),
    });
});

afterEach(() => cleanup());
