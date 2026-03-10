import { revalidatePath } from 'next/cache';
import { NextRequest, NextResponse } from 'next/server';

export async function POST(request: NextRequest) {
    const body = await request.json();
    const secret = body.secret;
    const path = body.path;

    // Check if token is correct (security check)
    if (secret !== process.env.REVALIDATE_TOKEN) {
        return NextResponse.json({ message: 'Invalid token' }, { status: 401 });
    }

    if (!path) {
        return NextResponse.json({ message: 'Path is required' }, { status: 400 });
    }

    try {
        revalidatePath(path);

        return NextResponse.json({ revalidated: true, now: Date.now() });
    } catch (err) {
        return NextResponse.json({ message: 'Error revalidating' }, { status: 500 });
    }
}