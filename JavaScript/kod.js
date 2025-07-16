
const fs = require('node:fs');

const result = JSON.parse(fs.readFileSync("transactions.json", "UTF-8"));
console.log(transform(result));


    function transform(oldData) {
      const newDataSet = separe(oldData, 'year', 'amount', 'createdAt', 'customerName', 'state');

      const grouped = newDataSet
       .filter(item => item.state === "canceled")
        .reduce((newObj, item) => ({
         ...newObj,
      [item.year]: [...(newObj[item.year] || []), item]
      }), {});

      const groupYear = Object.entries(grouped)
        .sort((a, b) => b[0] - a[0])
         .map(([year, items]) => [
           year,
           items
            .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt)) 
            .map(({ amount, createdAt, customerName }) => ({ amount, createdAt, customerName }))
         ]);

      return Object.fromEntries(groupYear);
    }

    function separe(array, ...keys) {
      return array.map(x =>
        keys.reduce((obj, key) =>
          Object.assign(obj, { [key]: x[key] }),
          {}
        )
      );
    }