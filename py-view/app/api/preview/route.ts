// app/api/preview/route.ts
import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const path = searchParams.get('path');
  const expires = searchParams.get('expires');
  const signature = searchParams.get('signature');

  const response = await fetch(`http://admin:8000/api/preview/verify?path=${path}&expires=${expires}&signature=${signature}`);
  const data = await response.json();

  if (!data.valid) return new Response('Unauthorized', { status: 401 });

  const cookieStore = await cookies();
  cookieStore.set('pyxis_preview', data.preview_token, {
    httpOnly: true,
    path: '/',
  });

  // Przekierowanie na pełną ścieżkę
  redirect(path === 'homepage' ? '/' : `/${path}`);
}