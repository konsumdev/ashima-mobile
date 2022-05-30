import { Component, OnInit } from '@angular/core';
import { AppComponent } from 'src/app/app.component';
import { GovloansProvider } from 'src/app/services/govloans/govloans';

@Component({
  selector: 'app-thirdloans',
  templateUrl: './thirdloans.page.html',
  styleUrls: ['./thirdloans.page.scss'],
})
export class ThirdloansPage implements OnInit {
  fakeUsers: Array<any>;
  fetchingAll: any;
  thirdLoans: any;

  constructor(
    private mainApp : AppComponent,
    private dedProv: GovloansProvider,
  ) { 
    this.mainApp.apiSessionChecker();
    this.fakeUsers = new Array(10);
    this.fetchingAll = true;
  }

  ngOnInit() {
    this.getThirdPartyLoans();
  }

  getThirdPartyLoans() {
    this.dedProv.getThirdLoans().then( res => {
        this.fetchingAll = false;
        let rslt: any = res;
        if (rslt) {
            if (rslt.result) {
                this.thirdLoans = rslt.third_pt_loans;
            }
        }            

    }).catch(error => {
        this.fetchingAll = false;
        console.log(error);
    });
  }

}
