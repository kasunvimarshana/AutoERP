import { useEffect, useRef } from 'react';
import type { SetURLSearchParams } from 'react-router-dom';
import { sourceKey } from '../sourceIdentity';

export interface InitialSourceParamDefinition<TType extends string> {
    sourceType: TType;
    paramNames: string[];
}

export interface InitialSourceCommand<TType extends string> {
    sourceType: TType;
    sourceId: number;
    key: string;
    paramName: string;
}

export function useInitialSourceParam<TType extends string>({
    searchParams,
    setSearchParams,
    definitions,
    isUnavailable,
    onProcess,
}: {
    searchParams: URLSearchParams;
    setSearchParams: SetURLSearchParams;
    definitions: InitialSourceParamDefinition<TType>[];
    isUnavailable?: (key: string) => boolean;
    onProcess: (command: InitialSourceCommand<TType>) => Promise<void>;
}) {
    const processedKeysRef = useRef(new Set<string>());
    const processingKeysRef = useRef(new Set<string>());

    useEffect(() => {
        const command = findInitialSourceCommand(searchParams, definitions);
        if (!command) return;

        if (processedKeysRef.current.has(command.key) || isUnavailable?.(command.key)) {
            consumeParam(setSearchParams, command.paramName);
            return;
        }

        if (processingKeysRef.current.has(command.key)) return;

        processingKeysRef.current.add(command.key);

        void (async () => {
            try {
                await onProcess(command);
            } finally {
                processingKeysRef.current.delete(command.key);
                processedKeysRef.current.add(command.key);
                consumeParam(setSearchParams, command.paramName);
            }
        })();
    }, [definitions, isUnavailable, onProcess, searchParams, setSearchParams]);
}

function findInitialSourceCommand<TType extends string>(
    searchParams: URLSearchParams,
    definitions: InitialSourceParamDefinition<TType>[],
): InitialSourceCommand<TType> | null {
    for (const definition of definitions) {
        for (const paramName of definition.paramNames) {
            const rawId = searchParams.get(paramName);
            const normalizedKey = sourceKey(definition.sourceType, rawId);
            if (!normalizedKey) continue;

            const [, rawNormalizedId] = normalizedKey.split(':');
            const normalizedId = Number(rawNormalizedId);
            if (!Number.isFinite(normalizedId)) continue;

            return {
                sourceType: definition.sourceType,
                sourceId: normalizedId,
                key: normalizedKey,
                paramName,
            };
        }
    }

    return null;
}

function consumeParam(setSearchParams: SetURLSearchParams, paramName: string) {
    setSearchParams((current) => {
        const next = new URLSearchParams(current);
        next.delete(paramName);
        return next;
    }, { replace: true });
}
