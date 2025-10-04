const express = require('express');
const crypto = require('crypto');
const axios = require('axios');
const app = express();
const port = 3000;

// App constants
const APP_ID = "24178";
const REWARD = "25";
const CURRENCY = "0.50";
const SECRET_KEY = "ff305841d62fd579579998714c7e70cb9f76dd6e";
const OFFER_ID = "test_campaign";

// Endpoint to trigger TheoremReach postback
app.get('/theoremreach-postback', async (req, res) => {
    const user_id = req.query.user_id;
    const tx_id = req.query.tx_id;
    const ip = req.query.ip || req.ip;

    if(!user_id || !tx_id){
        return res.status(400).send("Missing user_id or tx_id");
    }

    // Generate SHA256 hash
    const hashString = user_id + APP_ID + REWARD + CURRENCY + SECRET_KEY;
    const hash = crypto.createHash('sha256').update(hashString).digest('hex');

    // Build TheoremReach URL
    const postbackUrl = `https://padeco1981.github.io/app/api/theoremreach/route.js` +
        `?user_id=${user_id}` +
        `&app_id=${APP_ID}` +
        `&reward=${REWARD}` +
        `&status=1` +
        `&currency=${CURRENCY}` +
        `&screenout=0` +
        `&profiler=0` +
        `&tx_id=${tx_id}` +
        `&ip=${ip}` +
        `&offer_id=${OFFER_ID}` +
        `&debug=true` +
        `&hash=${hash}`;

    try {
        // Automatically call the postback
        const response = await axios.get(postbackUrl);
        res.send({
            message: "Postback sent successfully",
            postbackUrl,
            theoremReachResponse: response.data
        });
    } catch (error) {
        res.status(500).send({
            message: "Error sending postback",
            error: error.message
        });
    }
});

app.listen(port, () => {
    console.log(`TheoremReach postback server running at http://localhost:${port}`);
});
