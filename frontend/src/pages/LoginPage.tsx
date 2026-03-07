import { useState } from 'react';
import { useAuthStore } from '../store/authStore';
import { authService } from '../services/authService';

/**
 * Login form page.
 * On success, stores the Bearer token and user profile via authStore.
 */
export default function LoginPage() {
  const { setAuth } = useAuthStore();
  const [email,    setEmail]    = useState('');
  const [password, setPassword] = useState('');
  const [error,    setError]    = useState<string | null>(null);
  const [loading,  setLoading]  = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const loginRes = await authService.login({ email, password });
      const token    = loginRes.data.token;

      // Fetch user profile
      const meRes = await authService.me();
      const user  = meRes.data;

      setAuth({
        id:       user.id,
        name:     user.name,
        email:    user.email,
        tenantId: user.tenant_id,
      }, token);

    } catch {
      setError('Invalid credentials. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const inputStyle: React.CSSProperties = {
    width: '100%', padding: '10px 14px', border: '1px solid #cbd5e1',
    borderRadius: 6, fontSize: 14, outline: 'none',
  };

  return (
    <div style={{
      minHeight: '100vh', display: 'flex', alignItems: 'center',
      justifyContent: 'center', background: '#f8fafc',
    }}>
      <div style={{
        background: '#fff', padding: 40, borderRadius: 12,
        boxShadow: '0 4px 24px rgba(0,0,0,0.08)', width: 360,
      }}>
        <h2 style={{ fontSize: 24, fontWeight: 700, marginBottom: 8 }}>Sign in</h2>
        <p style={{ color: '#64748b', marginBottom: 24, fontSize: 14 }}>
          SaaS Inventory Management
        </p>

        {error && (
          <div style={{ background: '#fef2f2', color: '#b91c1c', padding: '10px 14px', borderRadius: 6, marginBottom: 16, fontSize: 14 }}>
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div style={{ marginBottom: 16 }}>
            <label style={{ display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 6 }}>
              Email
            </label>
            <input
              type="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              style={inputStyle}
              placeholder="admin@default.local"
              required
            />
          </div>

          <div style={{ marginBottom: 24 }}>
            <label style={{ display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 6 }}>
              Password
            </label>
            <input
              type="password"
              value={password}
              onChange={e => setPassword(e.target.value)}
              style={inputStyle}
              placeholder="••••••••"
              required
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            style={{
              width: '100%', padding: '11px 0', background: loading ? '#93c5fd' : '#3b82f6',
              color: '#fff', border: 'none', borderRadius: 6, fontSize: 15,
              fontWeight: 600, cursor: loading ? 'not-allowed' : 'pointer',
            }}
          >
            {loading ? 'Signing in…' : 'Sign in'}
          </button>
        </form>
      </div>
    </div>
  );
}
