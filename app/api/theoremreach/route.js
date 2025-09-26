import crypto from "crypto";
import { NextResponse } from "next/server";

// TheoremReach will call this endpoint like:
// https://padeco1981.vercel.app/api/theoremreach?user_id=123&tx_id=abc&reward=10&currency=USD&hash=<sha1>

export async function GET(req) {
  try {
    const { searchParams } = new URL(req.url);

    // Extract provided hash
    const providedHash = (searchParams.get("hash") || "").toString();

    // Rebuild the URL without "hash"
    const urlWithoutHash = new URL(req.url);
    urlWithoutHash.searchParams.delete("hash");

    // Compute SHA-1 of URL (as TheoremReach expects)
    const computedHash = crypto
      .createHash("sha1")
      .update(urlWithoutHash.toString())
      .digest("hex");

    if (!providedHash || providedHash !== computedHash) {
      console.warn("❌ Invalid hash", { providedHash, computedHash });
      return NextResponse.json({ error: "Invalid hash" }, { status: 400 });
    }

    // ✅ Hash is valid → read params
    const user_id = searchParams.get("user_id");
    const tx_id = searchParams.get("tx_id");
    const reward = searchParams.get("reward");
    const currency = searchParams.get("currency");
    const reversal = searchParams.get("reversal");
    const debug = searchParams.get("debug");

    // 👉 Here you should:
    // - Check if tx_id is already processed (to avoid double credit)
    // - Credit "user_id" with the "reward" amount in your system
    // - Log transaction if needed

    console.log("✅ Valid callback", {
      user_id,
      tx_id,
      reward,
      currency,
      reversal,
      debug,
    });

    // Send 200 OK so TheoremReach doesn’t retry
    return new NextResponse("OK", { status: 200 });
  } catch (err) {
    console.error("⚠️ Callback error:", err);
    return NextResponse.json({ error: "Server error" }, { status: 500 });
  }
}
