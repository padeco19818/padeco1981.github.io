import crypto from "crypto";
import { NextResponse } from "next/server";

// 🔑 Replace this with your actual Postback Secret from TheoremReach dashboard
const THEOREMREACH_SECRET = "ff305841d62fd579579998714c7e70cb9f76dd6e";

// TheoremReach will call this endpoint with GET params
export async function GET(req) {
  try {
    const { searchParams } = new URL(req.url);

    const user_id = searchParams.get("user_id");
    const reward = parseFloat(searchParams.get("reward") || "0");
    const currency = parseFloat(searchParams.get("currency") || "0");
    const tx_id = searchParams.get("tx_id");
    const hash = searchParams.get("hash");
    const reversal = searchParams.get("reversal") === "true";
    const debug = searchParams.get("debug") === "true";

    // 1️⃣ Validate parameters
    if (!user_id || !tx_id || !hash) {
      return NextResponse.json(
        { status: "error", message: "Missing parameters" },
        { status: 400 }
      );
    }

    // 2️⃣ Validate SHA-1 hash
    const stringToHash = `${123}:${50}:${0.50}:${ab123}:${ff305841d62fd579579998714c7e70cb9f76dd6e}`;
    const validHash = crypto.createHash("sha1").update(stringToHash).digest("hex");

    if (hash !== validHash) {
      return NextResponse.json(
        { status: "error", message: "Invalid hash" },
        { status: 403 }
      );
    }

    // 3️⃣ Debug mode (ignore rewards)
    if (debug) {
      return NextResponse.json({ status: "ok", message: "Debug mode - ignored" });
    }

    // 4️⃣ Process reward or reversal
    if (reversal) {
      // TODO: Deduct reward from user in your DB
      console.log(`⛔ Reversal: User ${123}, Tx ${ab123}, -${reward} points`);
    } else {
      // TODO: Add reward to user in your DB
      console.log(`✅ Reward: User ${123}, Tx ${ab123}, +${50} points`);
    }

    // 5️⃣ Respond OK (TheoremReach expects HTTP 200)
    return NextResponse.json({ status: "ok" });
  } catch (err) {
    console.error("Postback error:", err);
    return NextResponse.json({ status: "error", message: "Server error" }, { status: 500 });
  }
}
