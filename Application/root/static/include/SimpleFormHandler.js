class SimpleFormHandler {
    constructor (form, msgbox) {
        this.handle_ = form;
        for (let input of form.getElementsByTagName("input")) {
            if (input.type === "submit") {
                input.onclick = (e) => this.handleSubmit(e);
                break;
            }
        }
        this.msgbox_ = msgbox;
    }


    handleSubmit(event) {
        event.preventDefault();
        console.log("Prevented disaster");

        let XHR = new XMLHttpRequest();
        let FD  = new FormData(this.handle_);

        XHR.addEventListener('load', (e) => {
            console.log("LOLOLO");
            let resp = JSON.parse(e.target.responseText);
            let msg_type = resp["success"] ? "Success" : "Error";
            let msg = resp["messages"] ? resp["messages"].join("<br>") : "We've got server acceptance!";
            if (this.msgbox_)
                this.msgbox_.appendChild(MakeMessage(msg_type, msg, msg_type));
            else
                alert(msg);
        });

        XHR.addEventListener('error', function(event) {
            console.log('Oups! Something went wrong.');
            if (this.msgbox_)
                this.msgbox_.children = MakeMessage("Error", "Something went wrong...", "Error");
        });

        XHR.open("POST", this.handle_.action);

        XHR.send(FD);
        console.log("Data sent");
        if (this.msgbox_) {
            this.msgbox_.appendChild(MakeMessage("Data sent", "Data was sent to the server. Now awaiting response."));
        }
    }
}