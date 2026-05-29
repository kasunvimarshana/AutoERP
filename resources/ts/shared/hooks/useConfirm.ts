import { useDisclosure } from './useDisclosure';

export function useConfirm() {
    return useDisclosure(false);
}
