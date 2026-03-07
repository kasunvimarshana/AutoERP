import { useState, useCallback, type ChangeEvent } from 'react';

type Validators<T> = {
  [K in keyof T]?: (value: T[K]) => string | null;
};

interface UseFormReturn<T> {
  values: T;
  errors: Partial<Record<keyof T, string>>;
  handleChange: (e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => void;
  handleSubmit: (fn: (values: T) => Promise<void>) => (e: React.FormEvent) => Promise<void>;
  setValues: React.Dispatch<React.SetStateAction<T>>;
  validate: () => boolean;
  reset: () => void;
}

export function useForm<T extends object>(
  initialValues: T,
  validators?: Validators<T>,
): UseFormReturn<T> {
  const [values, setValues] = useState<T>(initialValues);
  const [errors, setErrors] = useState<Partial<Record<keyof T, string>>>({});

  const validate = useCallback((): boolean => {
    if (!validators) return true;
    const newErrors: Partial<Record<keyof T, string>> = {};
    for (const key in validators) {
      const validator = validators[key];
      if (validator) {
        const error = validator(values[key]);
        if (error) newErrors[key] = error;
      }
    }
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  }, [values, validators]);

  const handleChange = useCallback(
    (e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
      const { name, value, type } = e.target;
      const checked = (e.target as HTMLInputElement).checked;
      setValues((prev) => ({
        ...prev,
        [name]: type === 'checkbox' ? checked : value,
      }));
      setErrors((prev) => {
        if (!prev[name as keyof T]) return prev;
        const next = { ...prev };
        delete next[name as keyof T];
        return next;
      });
    },
    [],
  );

  const handleSubmit =
    (fn: (values: T) => Promise<void>) =>
    async (e: React.FormEvent) => {
      e.preventDefault();
      if (!validate()) return;
      await fn(values);
    };

  const reset = useCallback(() => {
    setValues(initialValues);
    setErrors({});
  }, [initialValues]);

  return { values, errors, handleChange, handleSubmit, setValues, validate, reset };
}
