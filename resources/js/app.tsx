/// <reference types="vite/client" />
import React from 'react';
import { createRoot } from 'react-dom/client';

import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from 'sonner';

import '../css/app.css';

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });
    return pages[`./Pages/${name}.tsx`];
  },
  setup({ el, App, props }) {
    createRoot(el).render(
      <>
        <App {...props} />
        <Toaster richColors position="top-right" />
      </>
    );
  },
});
