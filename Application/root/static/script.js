class UIControler
{
    constructor() {
        this.server_ = null;
        this.shown_ = null;
        this.currentServer_ = null;
        this.display_ = document.getElementById("main");

        document.getElementById("button_select_server").onclick = () => this.showServerSelection();
        document.getElementById("button_add_server").onclick = () => this.showAddServerForm();

        this.showServerSelection();
    }


    setServer(server) {
        this.server_ = server;
        document.getElementById("server_header").textContent = server.name;
    }


    showServerSelection() {
        console.log("Select server");
        let xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = (e) => {
            if (e.target.readyState !== 4)
                return;
            this.resetDisplay_();
            if (e.target.status !== 200) {
                this.display_.appendChild(MakeMessage("Status " + e.target.status,
                    "Server responded with status " + status));
                return;
            }

            let servers = JSON.parse(e.target.responseText);
            if (!servers.success) {
                this.display_.appendChild(MakeMessage("Error", servers["message"]));
                return;
            }

            for (let server of servers.result)
                this.putServerDiv_(server);
        };
        this.resetDisplay_(true);
        xmlhttp.open("GET", "/api/servers/get.php", true);
        xmlhttp.send();
    }


    showAddServerForm() {
        console.log("Add server");
        let xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = (e) => {
            if (e.target.readyState !== 4)
                return;
            this.resetDisplay_();
            if (e.target.status !== 200) {
                this.display_.appendChild(MakeMessage("Unknown error", "Unknown error happened and we could not "
                    + "load the form for you."));
                return;
            }

            this.display_.innerHTML = e.target.responseText;
            let msg = document.createElement("div");
            this.display_.appendChild(msg);
            new SimpleFormHandler(this.display_.getElementsByTagName("form")[0], msg);
        };
        this.resetDisplay_(true);
        xmlhttp.open("GET", "/static/forms/AddServer.html", true);
        xmlhttp.send();
    }


    resetDisplay_(loading) {
        while (this.display_.firstChild)
            this.display_.removeChild(this.display_.firstChild);
        if (loading) {
            let img = document.createElement("img");
            img.src = "static/icons/ajax_loader.gif";
            this.display_.appendChild(img);
        }
    }


    putServerDiv_(server) {
        let div = document.createElement("div");
        div.classList.add("ServerNode");

        let ico = document.createElement("img");
        ico.classList.add("ServerIcon");
        ico.src = "/static/icons/server.png";

        let h = document.createElement("span");
        h.classList.add("ServerHeader");
        h.textContent = server.name;

        let llf = document.createElement("span");
        llf.classList.add("Label");
        llf.textContent = "Default log format:";
        let lf = document.createElement("span");
        lf.classList.add("ServerLogFormat");
        lf.textContent = server.defaultLogFormat;

        let ddsc = document.createElement("span");
        ddsc.classList.add("Label");
        ddsc.textContent = "Description:";
        let dsc = document.createElement("span");
        dsc.classList.add("ServerDescription");
        dsc.textContent = server.description;

        let lcol = document.createElement("div");
        lcol.classList.add("ServerLCol");
        lcol.appendChild(ico);
        div.appendChild(lcol);

        let rcol = document.createElement("div");
        rcol.classList.add("ServerRCol");
        rcol.appendChild(h);
        rcol.appendChild(llf);
        rcol.appendChild(lf);
        rcol.appendChild(ddsc);
        rcol.appendChild(dsc);
        div.appendChild(rcol);

        this.display_.appendChild(div);
        div.onclick = (e) => {
            this.setServer(server);
            for (let d of document.getElementsByClassName("SelectedServer"))
                d.classList.remove("SelectedServer");
            div.classList.add("SelectedServer");
        };

        if (this.server_ && server.name === this.server_.name) {
            div.classList.add("SelectedServer");
        }
    }
}


function MakeMessage(header, text, type="Info") {
    let msg = document.createElement("div");
    msg.classList.add("Message", type);
    let h = document.createElement("h3");
    h.textContent = header;
    let p = document.createElement("p");
    p.textContent = text;
    msg.appendChild(h);
    msg.appendChild(p);
    return msg;
}
