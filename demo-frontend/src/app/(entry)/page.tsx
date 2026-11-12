import { redirect } from "next/navigation";
import { getLocales } from "@/lib/cms/api";
export const dynamic = "force-dynamic";
export default async function Index() {
  const locales = await getLocales();
  redirect(`/${locales.default}`);
}
