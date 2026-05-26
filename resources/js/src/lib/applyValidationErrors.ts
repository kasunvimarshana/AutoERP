import type { FieldValues, Path, UseFormSetError } from 'react-hook-form';
import type { ApiValidationErrors } from '../types/api';

type ApplyValidationErrorsOptions = {
    onUnhandled?: (message: string, field: string) => void;
};

export function applyValidationErrors<TFieldValues extends FieldValues>(
    errors: ApiValidationErrors,
    setError: UseFormSetError<TFieldValues>,
    options: ApplyValidationErrorsOptions = {},
) {
    for (const [field, messages] of Object.entries(errors)) {
        const message = messages[0];

        if (!message) {
            continue;
        }

        setError(field as Path<TFieldValues>, { message });
    }
}
