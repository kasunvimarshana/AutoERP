import React, { useEffect, useState } from 'react';
import { useKeycloak } from '../auth/KeycloakProvider';
import { productApi, configureAxiosAuth } from '../services/api';

const ProductList = () => {
    const { token, hasRole } = useKeycloak();
    const [products, setProducts] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        configureAxiosAuth(token);
        fetchData();
    }, [token]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const res = await productApi.fetchProducts({
                sort: '-created_at',
                limit: 10,
                page: 1,
            });
            setProducts(res.data.data);
        } catch (e) {
            console.error('Failed fetching', e);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id: number) => {
        try {
            await productApi.deleteProduct(id);
            fetchData();
        } catch (error) {
            console.error('Failed to delete', error);
        }
    };

    return (
        <div style={{ padding: '40px', maxWidth: '1200px', margin: '0 auto' }} className="animate-slide-up">
            <header style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '40px' }}>
                <h1 style={{ fontSize: '2.5rem', fontWeight: 800, letterSpacing: '-1px' }}>
                    Inventory <span style={{ color: 'var(--accent-color)' }}>Hub</span>
                </h1>
                {hasRole('admin') && (
                    <button className="btn btn-primary" style={{ padding: '12px 24px' }}>
                        + Create New Product
                    </button>
                )}
            </header>

            {loading ? (
                <div style={{ textAlign: 'center', padding: '100px', color: 'var(--text-muted)' }}>
                    <div style={{
                        width: '40px', height: '40px', border: '3px solid var(--glass-border)',
                        borderTop: '3px solid var(--accent-color)', borderRadius: '50%',
                        animation: 'spin 1s linear infinite', margin: '0 auto 15px'
                    }} />
                    Loading secure catalog...
                </div>
            ) : (
                <div className="glass-container animate-slide-up" style={{ animationDelay: '0.1s' }}>
                    <div style={{ padding: '24px', borderBottom: '1px solid var(--glass-border)' }}>
                        <h2 style={{ fontSize: '1.2rem', fontWeight: 600 }}>Active Products</h2>
                        <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem', marginTop: '4px' }}>
                            Manage and orchestrate your microservice inventory data.
                        </p>
                    </div>
                    <table className="modern-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th>Cost</th>
                                <th>Context Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.length === 0 ? (
                                <tr>
                                    <td colSpan={5} style={{ textAlign: 'center', padding: '40px' }}>
                                        No items found in Keycloak authorized scope.
                                    </td>
                                </tr>
                            ) : (
                                products.map((p, idx) => (
                                    <tr key={p.id} style={{ animationDelay: `${idx * 0.05}s` }} className="animate-slide-up">
                                        <td style={{ fontWeight: 600, color: 'var(--text-muted)' }}>#{p.id}</td>
                                        <td>
                                            <span style={{
                                                background: 'rgba(99, 102, 241, 0.15)', color: 'var(--accent-hover)',
                                                padding: '4px 10px', borderRadius: '12px', fontSize: '0.8rem', fontWeight: 600
                                            }}>
                                                {p.sku}
                                            </span>
                                        </td>
                                        <td style={{ fontWeight: 600 }}>{p.name}</td>
                                        <td style={{ color: '#10b981', fontWeight: 600 }}>${p.price}</td>
                                        <td>
                                            {hasRole('admin') ? (
                                                <button
                                                    onClick={() => handleDelete(p.id)}
                                                    className="btn btn-danger"
                                                    style={{ padding: '6px 14px', fontSize: '0.8rem' }}
                                                >
                                                    Revoke
                                                </button>
                                            ) : (
                                                <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem' }}>View Only</span>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    <style>{`
             @keyframes spin { 100% { transform: rotate(360deg); } }
          `}</style>
                </div>
            )}
        </div>
    );
};

export default ProductList;
