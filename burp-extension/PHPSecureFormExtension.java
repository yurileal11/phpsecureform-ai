
// PHPSecureFormExtension.java - skeleton with control polling (English)
// This file is a starting point. You must adapt it to your Burp/Montoya API version.
// Key ideas implemented as pseudocode and comments:
// - Poll /api/control to get { enabled: bool, allow_replay: bool }
// - If enabled == false, do not forward intercepted traffic to the PoC endpoint
// - Provide a start/stop toggle in the extension UI that sends POST to /api/control with X-PHSF-Token header
//
// The code below is illustrative and will not compile verbatim against a specific Burp jar without adjustments.

public class PHPSecureFormExtension /* implements IBurpExtender, IHttpListener */ {

    // Example polling method (use appropriate scheduled executor inside your extension)
    private void pollControl(String callbackHost, String token) {
        try {
            java.net.http.HttpClient client = java.net.http.HttpClient.newHttpClient();
            java.net.URI uri = new java.net.URI("http://" + callbackHost + "/api/control");
            java.net.http.HttpRequest req = java.net.http.HttpRequest.newBuilder()
                .uri(uri)
                .GET()
                .build();
            java.net.http.HttpResponse<String> resp = client.send(req, java.net.http.HttpResponse.BodyHandlers.ofString());
            if (resp.statusCode() == 200) {
                System.out.println("Control: " + resp.body());
                // parse JSON and set a local flag 'enabled' accordingly
            }
        } catch (Exception e) {
            // handle/log
        }
    }

    // Example forward logic (pseudocode) in your HTTP listener
    private void onHttpMessage(Object request, Object response) {
        boolean enabled = true; // read from local flag set by pollControl
        if (!enabled) return; // skip forwarding when disabled
        String json = buildJsonFromRequestResponse(request, response);
        forwardToPoC(json, "<callback-host>", "<token>");
    }

    // UI: implement a simple JButton "Start"/"Stop" that calls POST /api/control with token
    private void sendControlCommand(String callbackHost, String token, boolean enable, boolean allowReplay) {
        try {
            java.net.http.HttpClient client = java.net.http.HttpClient.newHttpClient();
            java.net.http.HttpRequest req = java.net.http.HttpRequest.newBuilder()
                .uri(new java.net.URI("http://" + callbackHost + "/api/control"))
                .header("Content-Type","application/json")
                .header("X-PHSF-Token", token)
                .POST(java.net.http.HttpRequest.BodyPublishers.ofString("{"enabled":" + (enable?"true":"false") + ","allow_replay":" + (allowReplay?"true":"false") + "}"))
                .build();
            java.net.http.HttpResponse<String> resp = client.send(req, java.net.http.HttpResponse.BodyHandlers.ofString());
            System.out.println("Control set: " + resp.body());
        } catch (Exception e) {
            // handle
        }
    }

    // The rest of the extension: implement buildJsonFromRequestResponse and forwardToPoC as in earlier skeleton.
}
