import { Pipe, PipeTransform } from '@angular/core';
import * as moment from 'moment';

@Pipe({
  name: 'customDatetime'
})
export class CustomDatetimePipe implements PipeTransform {

  transform(value: string) {
    return moment(value, "YYYY-MM-DD HH:mm:ss").format("DD-MMM-YY hh:mm A");
  }

}
