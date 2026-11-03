import net from "node:net";
import fs from "node:fs";
const directory = process.argv[2];
const server = net.createServer((socket) => {
    socket.setEncoding("utf8");
    socket.write("220 localhost test SMTP\r\n");
    let buffer = "",
        data = false,
        message = "";
    socket.on("error", () => {});
    socket.on("data", (chunk) => {
        buffer += chunk;
        while (buffer.includes("\r\n")) {
            const end = buffer.indexOf("\r\n"),
                line = buffer.slice(0, end);
            buffer = buffer.slice(end + 2);
            const mode = fs.readFileSync(`${directory}/mode`, "utf8").trim();
            if (data) {
                if (line === ".") {
                    data = false;
                    fs.appendFileSync(
                        `${directory}/messages`,
                        JSON.stringify(message) + "\n",
                    );
                    message = "";
                    if (mode === "hold") continue;
                    if (mode === "disconnect") {
                        socket.destroy();
                        return;
                    }
                    socket.write("250 accepted\r\n");
                } else message += line + "\r\n";
            } else if (/^EHLO|^HELO/i.test(line))
                socket.write("250 localhost\r\n");
            else if (/^RCPT/i.test(line) && mode === "temporary")
                socket.write("450 try later\r\n");
            else if (/^RCPT/i.test(line) && mode === "permanent")
                socket.write("550 mailbox unavailable\r\n");
            else if (/^DATA/i.test(line)) {
                data = true;
                socket.write("354 send data\r\n");
            } else if (/^QUIT/i.test(line)) socket.end("221 bye\r\n");
            else socket.write("250 ok\r\n");
        }
    });
});
server.listen(0, "127.0.0.1", () =>
    fs.writeFileSync(`${directory}/port`, String(server.address().port)),
);
