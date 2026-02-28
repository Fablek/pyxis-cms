import Image from "next/image";
import Hero from '@theme/components/Hero'; 

export default function Home() {
  return (
    <div className="flex min-h-screen flex-col items-center bg-zinc-50 font-sans dark:bg-black">
      <main className="flex w-full max-w-5xl flex-col gap-12 py-20 px-6">
        
        <header className="flex items-center justify-between border-b pb-6 dark:border-zinc-800">
          <h1 className="text-xl font-bold tracking-tighter text-black dark:text-white">
            Pyxis <span className="text-blue-600">CMS</span> Engine
          </h1>
          <nav className="text-sm font-medium text-zinc-500">
            Mode: Developer
          </nav>
        </header>

        <section className="w-full">
           <Hero />
        </section>

        <section className="grid gap-4 sm:grid-cols-2">
          <div className="rounded-xl border border-zinc-200 p-6 dark:border-zinc-800">
            <h3 className="font-semibold text-black dark:text-white">Status Mostu</h3>
            <p className="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
              Komponenty są poprawnie zaciągane z folderu <code>/py-content/themes/default</code>.
            </p>
          </div>
          <div className="rounded-xl border border-zinc-200 p-6 dark:border-zinc-800">
            <h3 className="font-semibold text-black dark:text-white">Następny krok</h3>
            <p className="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
              Podłączenie API Laravela (py-admin) i pobranie danych dla bloków.
            </p>
          </div>
        </section>

      </main>
    </div>
  );
}