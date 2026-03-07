import { notFound } from 'next/navigation';

/**
 * 1. TYPE DEFINITIONS
 * Defines the contract between the Laravel API and the Next.js Frontend.
 */
interface PageData {
  id: string; // UUID
  title: string;
  content: any;
  full_url: string;
  is_password_protected: boolean;
  published_at: string;
  seo: any;
}

/**
 * 2. DATA ACCESS LAYER
 * Fetches page data from the Laravel backend based on the URL segments.
 */
async function getPageData(slugArray: string[] | undefined): Promise<PageData | null> {
  // Convert ['parent', 'child'] array into a 'parent/child' string. 
  const slug = slugArray?.length ? slugArray.join('/') : '';
  
  // Use the Docker service name 'admin' for internal container communication.
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://admin:8000/api';

  try {
    const res = await fetch(`${API_URL}/pages/${slug}`, { 
      // Force Server-Side Rendering (SSR) to ensure content editors see 
      // their changes immediately after saving in Filament.
      cache: 'no-store', 
      headers: { 'Accept': 'application/json' },
      // Abort the request if the backend takes longer than 5 seconds to respond.
      signal: AbortSignal.timeout(5000) 
    });

    if (!res.ok) return null;

    const json = await res.json();

    return json.data;
  } catch (error) {
    // Log connection issues to the server console for debugging.
    console.error("❌ API Fetch Error:", error);
    return null;
  }
}

/**
 * 3. MAIN PAGE COMPONENT
 * The entry point for all dynamic routes managed by the CMS.
 */
export default async function Page(props: { params: Promise<{ slug?: string[] }> }) {
  // In Next.js 15/16, 'params' is a Promise and must be explicitly awaited.
  const { slug } = await props.params;
  const data = await getPageData(slug);
  console.log(data);

  // Trigger the built-in Next.js 404 page if the slug doesn't exist in the database.
  if (!data) notFound();

  return (
    <main className="max-w-4xl mx-auto p-10 font-sans">
      <header className="mb-8 border-b pb-8">
        <h1 className="text-5xl font-extrabold text-slate-900 tracking-tight">
          {data.title}
        </h1>
        <p className="mt-2 text-slate-500 font-mono text-sm uppercase tracking-wider">
          📍 API Path: {data.full_url}
        </p>
      </header>

      <section className="prose prose-slate lg:prose-xl">
        {data.is_password_protected ? (
          <div className="bg-amber-50 border border-amber-200 p-6 rounded-lg text-amber-800 flex items-center shadow-sm">
            <span className="text-2xl mr-4">🔒</span>
            <p>This page is password protected. Access form coming soon.</p>
          </div>
        ) : (
          <div className="text-slate-700 leading-relaxed min-h-[200px] flex items-center justify-center border-2 border-dashed border-slate-200 rounded-xl">
             {/* Content placeholder - logic for Block Builder will go here */}
             <p className="italic text-slate-400">Content rendering engine pending...</p>
          </div>
        )}
      </section>
    </main>
  );
}