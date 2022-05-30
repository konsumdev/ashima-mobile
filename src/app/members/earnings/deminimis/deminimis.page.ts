import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { DeminimisProvider } from 'src/app/services/deminimis/deminimis';

@Component({
  selector: 'app-deminimis',
  templateUrl: './deminimis.page.html',
  styleUrls: ['./deminimis.page.scss'],
})
export class DeminimisPage implements OnInit {
  fakeUsers: Array<any>;
  fetchingAll: any;
  deminimis: any;

  constructor(
    private mainApp : AppComponent,
    private dimProv: DeminimisProvider,
  ) { 
    this.fakeUsers = new Array(10);
    this.fetchingAll = true;
  }

  ngOnInit() {
      this.mainApp.apiSessionChecker();
      this.getEmployeeDeminimis();
  }

  getEmployeeDeminimis() {
    this.dimProv.getDeminimis().then( res => {
        this.fetchingAll = false;
        let rslt: any = res;

        if (rslt) {
            if (rslt.result) {
                this.deminimis = rslt.de_minimis;
            }
            
        }
    }).catch( error => {
        this.fetchingAll = false;
        console.log(error);
    });
  }

}
