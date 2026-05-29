import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import {
    DocumentAttachmentPanel,
    DocumentCommentPanel,
    DocumentEventsTimeline,
    DocumentLinesTable,
    DocumentMetadataPanel,
    DocumentPermissionsPanel,
    DocumentPreviewPanel,
    DocumentRecordSummaryCard,
    DocumentRelationsTable,
    DocumentSourceReferencePanel,
    DocumentStatusActionPanel,
    DocumentVersionsTable,
} from '../components/DocumentComponents';
import { sourceReferences } from '../mock/documentMock';
import { documentApi } from '../services/documentApi';
import type { DocumentAttachment, DocumentComment, DocumentEvent, DocumentLine, DocumentPermission, DocumentRecord, DocumentRelation, DocumentVersion } from '../types/document.types';

const tabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Preview', value: 'preview' },
    { label: 'Lines / Items', value: 'lines' },
    { label: 'Source References', value: 'source' },
    { label: 'Attachments', value: 'attachments' },
    { label: 'Comments', value: 'comments' },
    { label: 'Relations', value: 'relations' },
    { label: 'Permissions', value: 'permissions' },
    { label: 'Events', value: 'events' },
    { label: 'Versions', value: 'versions' },
    { label: 'Audit / History', value: 'audit' },
];

export function DocumentRecordDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [document, setDocument] = useState<DocumentRecord | null>(null);
    const [attachments, setAttachments] = useState<DocumentAttachment[]>([]);
    const [comments, setComments] = useState<DocumentComment[]>([]);
    const [relations, setRelations] = useState<DocumentRelation[]>([]);
    const [lines, setLines] = useState<DocumentLine[]>([]);
    const [permissions, setPermissions] = useState<DocumentPermission[]>([]);
    const [events, setEvents] = useState<DocumentEvent[]>([]);
    const [versions, setVersions] = useState<DocumentVersion[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        const documentId = id ?? '';
        Promise.all([
            documentApi.getDocument(documentId),
            documentApi.listAttachments(documentId),
            documentApi.listComments(documentId),
            documentApi.listDocumentLines(documentId),
            documentApi.listRelations(documentId),
            documentApi.listPermissions(documentId),
            documentApi.listEvents(documentId),
            documentApi.listVersions(documentId),
        ]).then(([documentResponse, attachmentResponse, commentResponse, lineResponse, relationResponse, permissionResponse, eventResponse, versionResponse]) => {
            if (mounted) {
                setDocument(documentResponse.data);
                setAttachments(attachmentResponse.data);
                setComments(commentResponse.data);
                setLines(lineResponse.data);
                setRelations(relationResponse.data);
                setPermissions(permissionResponse.data);
                setEvents(eventResponse.data);
                setVersions(versionResponse.data);
            }
        }).catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load document detail.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    if (isLoading) return <EmptyState description="Loading document detail..." title="Loading document" />;
    if (error || !document) return <EmptyState description={error || 'Document record was not found.'} title="Unable to load document" />;

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/documents/records"><Button variant="secondary">Back</Button></Link><Button>Preview</Button></>} eyebrow="Document Record" subtitle="Record detail shows document shell, source references, workflow, versions, attachments, comments, and audit. Source modules own business values." title={document.documentNumber} />
            <DocumentRecordSummaryCard document={document} />
            <Card className="p-5"><Tabs active={activeTab} items={tabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? <div className="grid gap-5 xl:grid-cols-[1fr_340px]"><DocumentMetadataPanel /><DocumentStatusActionPanel document={document} /></div> : null}
            {activeTab === 'preview' ? <DocumentPreviewPanel /> : null}
            {activeTab === 'lines' ? <DocumentLinesTable lines={lines} /> : null}
            {activeTab === 'source' ? <DocumentSourceReferencePanel references={sourceReferences} /> : null}
            {activeTab === 'attachments' ? <DocumentAttachmentPanel attachments={attachments} /> : null}
            {activeTab === 'comments' ? <DocumentCommentPanel comments={comments} /> : null}
            {activeTab === 'relations' ? <DocumentRelationsTable relations={relations} /> : null}
            {activeTab === 'permissions' ? <DocumentPermissionsPanel permissions={permissions} /> : null}
            {activeTab === 'events' ? <DocumentEventsTimeline events={events} /> : null}
            {activeTab === 'versions' ? <DocumentVersionsTable versions={versions} /> : null}
            {activeTab === 'audit' ? <DocumentEventsTimeline events={events} /> : null}
        </div>
    );
}
