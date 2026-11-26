import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './auth/AuthContext.jsx';
import ProtectedRoute from './auth/ProtectedRoute.jsx';
import AppLayout from './layouts/AppLayout.jsx';
import Categories from './pages/Categories.jsx';
import Dashboard from './pages/Dashboard.jsx';
import Login from './pages/Login.jsx';
import Notifications from './pages/Notifications.jsx';
import Reports from './pages/Reports.jsx';
import RequestCreate from './pages/RequestCreate.jsx';
import RequestDetail from './pages/RequestDetail.jsx';
import Requests from './pages/Requests.jsx';
import Users from './pages/Users.jsx';

function AppRoutes() {
    return (
        <Routes>
            <Route path="/login" element={<Login />} />

            <Route element={<ProtectedRoute />}>
                <Route element={<AppLayout />}>
                    <Route path="/dashboard" element={<Dashboard />} />
                    <Route path="/requests" element={<Requests />} />
                    <Route path="/requests/:id" element={<RequestDetail />} />
                    <Route path="/notifications" element={<Notifications />} />
                </Route>
            </Route>

            <Route element={<ProtectedRoute roles={['estudiante']} />}>
                <Route element={<AppLayout />}>
                    <Route path="/requests/new" element={<RequestCreate />} />
                </Route>
            </Route>

            <Route element={<ProtectedRoute roles={['admin']} />}>
                <Route element={<AppLayout />}>
                    <Route path="/users" element={<Users />} />
                    <Route path="/categories" element={<Categories />} />
                </Route>
            </Route>

            <Route element={<ProtectedRoute roles={['admin', 'supervisor']} />}>
                <Route element={<AppLayout />}>
                    <Route path="/reports" element={<Reports />} />
                </Route>
            </Route>

            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
    );
}

createRoot(document.getElementById('root')).render(
    <React.StrictMode>
        <BrowserRouter>
            <AuthProvider>
                <AppRoutes />
            </AuthProvider>
        </BrowserRouter>
    </React.StrictMode>,
);