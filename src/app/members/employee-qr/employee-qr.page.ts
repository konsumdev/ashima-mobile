import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { ProfileProvider } from 'src/app/services/profile/profile';
import { NavigationExtras, Router } from '@angular/router';
import * as API_CONFIG from '../../services/api-config';

@Component({
  selector: 'app-employee-qr',
  templateUrl: './employee-qr.page.html',
  styleUrls: ['./employee-qr.page.scss'],
})
export class EmployeeQrPage implements OnInit {

    apiUrl : any;
    qrUrl : any;
    ayyDee: any;
    compAyDee: any;
    encodedData: '';
    encodeData: any;
    showQr: boolean = false;

    constructor(
        private mainApp : AppComponent,
        private profProv : ProfileProvider,
        private router: Router
    ) { 
        // this.mainApp.apiSessionChecker();
        this.apiUrl = API_CONFIG.API.BASE_URI;
    }

    ngOnInit() {        
        
        var sess = this.mainApp.getSessionDetails();
        // console.log(sess);
        
        if (sess) {
            this.ayyDee = sess.cloudid;
            this.compAyDee = sess.compId;
            // this.getQr(this.ayyDee, this.compAyDee);

            this.qrUrl = this.apiUrl+"api/profile/maketh/"+this.ayyDee+"/"+this.compAyDee;
            // var isExist = this.imageExists(this.qrUrl);
            // if (isExist) {
            //     this.showQr = true;
            // }
            console.log(this.qrUrl);            
            // console.log(isExist);
        }
    }

    imageExists(image_url: any){

        var http = new XMLHttpRequest();
    
        http.open('HEAD', image_url, false);
        http.send();
    
        return http.status != 404;
    
    }

    getQr(id : any, comp_id : any) {
        console.log("get qr fxn");
        this.profProv.getEmployeeQr(id, comp_id)
        .then( res => {
            let deta: any; 
            deta = res;
            // this.ayyDee = deta.cloud_id;
            // this.compAyDee = deta.cloud_id;
            // this.qrUrl = this.apiUrl+"api/profile/maketh/"+this.ayyDee+"/"+this.compAyDee;
            console.log(deta);
        })
        .catch(error => {            
            console.log(error);
        });
    }
}
