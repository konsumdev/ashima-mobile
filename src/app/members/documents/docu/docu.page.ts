import { Component, OnInit } from '@angular/core';
import { DocumentProvider } from 'src/app/services/document/document';
import { AppComponent } from 'src/app/app.component';

@Component({
  selector: 'app-docu',
  templateUrl: './docu.page.html',
  styleUrls: ['./docu.page.scss'],
})
export class DocuPage implements OnInit {
  currentPage: number = 1;
  totalPages: any;
  documentDetails: any;
  docRes: any;

  constructor(
    private docProv: DocumentProvider,
    private mainApp : AppComponent,
  ) { 
    this.mainApp.apiSessionChecker();
  }

  ngOnInit() {
    this.getDocuments();
  }

  getDocuments() {
    this.docProv.getDocuments(this.currentPage+'').then( res => {
        this.docRes = res;

        this.documentDetails = this.docRes.list;
        this.totalPages = parseInt(this.docRes.total, 10);
        this.currentPage = parseInt(this.docRes.page, 10);

    }).catch(error => {
        console.log(error);
    });
  }

  doInfinite(infiniteScroll: any) {

    this.currentPage = this.currentPage+1;
    
    setTimeout(() => {
        this.docProv.getDocuments(this.currentPage+'').then( res => {
            this.docRes = res;

            for(let i=0; i<this.docRes.list.length; i++) {
                this.documentDetails.push(this.docRes.list[i]);
            }

            infiniteScroll.target.complete();
        }).catch(error => {
            infiniteScroll.target.complete();
            console.log(error);
        });


        if (this.currentPage == this.totalPages) {
          infiniteScroll.target.disabled = true;
        }
    }, 1000);
  }

}
