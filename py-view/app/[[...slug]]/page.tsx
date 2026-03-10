import { notFound } from 'next/navigation';
import { cookies } from 'next/headers';

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
  is_preview: boolean;
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

  const cookieStore = await cookies();
  const previewToken = cookieStore.get('pyxis_preview')?.value;
  
  // Use the Docker service name 'admin' for internal container communication.
  const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://admin:8000/api';

  // Setup headers
  const headers: HeadersInit = { 
    'Accept': 'application/json' 
  };

  // If have token
  if (previewToken) {
    headers['X-Pyxis-Preview'] = previewToken;
  }

  try {
    const res = await fetch(`${API_URL}/pages/${slug}`, { 
      // Force Server-Side Rendering (SSR) to ensure content editors see 
      // their changes immediately after saving in Filament.
      cache: 'no-store', 
      headers: headers,
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
  const { slug } = await props.params;
  const data = await getPageData(slug);

  if (!data) notFound();

  return (
    <main className="max-w-4xl mx-auto p-10 font-sans">
      
      {/* PASEK PODGLĄDU - Wyświetli się tylko w trybie preview */}
      {data.is_preview && (
        <div className="fixed top-0 left-0 w-full bg-black text-white py-2 px-4 flex justify-between items-center z-50 text-sm font-bold shadow-xl">
          <div className="flex items-center gap-2">
            <span className="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
            TRYB PODGLĄDU AKTYWNY (SZKIC)
          </div>
          <a 
            href="/api/exit-preview" 
            className="bg-white text-black px-3 py-1 rounded hover:bg-slate-200 transition"
          >
            Wyłącz podgląd
          </a>
        </div>
      )}

      <header className={`mb-8 border-b pb-8 ${data.is_preview ? 'mt-12' : ''}`}>
        <h1 className="text-5xl font-extrabold text-slate-900 tracking-tight">
          {data.title}
        </h1>
        {/* ... reszta nagłówka ... */}
      </header>

      {/* ... reszta renderowania strony ... */}
    </main>
  );
}